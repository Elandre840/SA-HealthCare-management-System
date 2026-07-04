import { createApiClient } from './api'

function jsonResponse(body: unknown, status = 200) {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  })
}

describe('createApiClient', () => {
  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('refreshes the session and retries authenticated requests after a 401', async () => {
    const onSessionRefresh = vi.fn()
    const onUnauthorized = vi.fn()
    const fetchMock = vi
      .spyOn(globalThis, 'fetch')
      .mockResolvedValueOnce(jsonResponse({ detail: 'Expired' }, 401))
      .mockResolvedValueOnce(
        jsonResponse({ access_token: 'new-access-token', token_type: 'bearer' }),
      )
      .mockResolvedValueOnce(jsonResponse({ ok: true }))

    const api = createApiClient({
      getAccessToken: () => 'expired-access-token',
      getRefreshToken: () => 'refresh-token',
      onSessionRefresh,
      onUnauthorized,
    })

    await expect(api.request('/auth/me')).resolves.toEqual({ ok: true })

    expect(fetchMock).toHaveBeenCalledTimes(3)
    expect(onSessionRefresh).toHaveBeenCalledWith({
      access_token: 'new-access-token',
      token_type: 'bearer',
    })
    expect(onUnauthorized).not.toHaveBeenCalled()

    const retryHeaders = fetchMock.mock.calls[2]?.[1]?.headers as Headers
    expect(retryHeaders.get('Authorization')).toBe('Bearer new-access-token')
  })
})
