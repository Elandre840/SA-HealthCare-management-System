# Clinical HMS Web

React/TypeScript frontend for the Clinical HMS rebuild.

**Project owner:** Mj Technologies  
**Developer:** Elandre Booth

## Current Status

This frontend currently contains the authentication shell:

- Vite + React + TypeScript project setup
- Tailwind CSS styling
- typed API client
- login screen
- access/refresh token handling
- protected route guard
- logout action
- top navigation shell
- role-based dashboard placeholders

The next frontend screens will follow the clinic workflow:

```text
Reception -> Nurse -> Doctor -> Pharmacist -> Completed
```

## Local Setup

1. Install dependencies:

```powershell
npm install
```

2. Copy the environment example:

```powershell
Copy-Item .env.example .env
```

3. Start the frontend:

```powershell
npm run dev -- --host 127.0.0.1
```

4. Open the app:

```text
http://127.0.0.1:5173/
```

The API should be running at the URL configured in `VITE_API_BASE_URL`.

For the current local setup, the backend usually runs here:

```text
http://127.0.0.1:8000
```

## Demo Accounts

All demo accounts use this password:

```text
Password123!
```

```text
admin@clinicdemo.co.za
reception@clinicdemo.co.za
nurse@clinicdemo.co.za
doctor@clinicdemo.co.za
pharmacist@clinicdemo.co.za
```

## Scripts

```powershell
npm run lint
npm run test
npm run build
```

## Notes For Collaborators

The frontend is intentionally thin at this stage. Most screens are placeholders until the backend patient workflow APIs are added.

Authentication is already wired against the FastAPI backend, so future pages should use the existing API client and protected layout instead of creating separate fetch logic.
