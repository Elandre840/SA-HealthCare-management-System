/**
 * Application bootstrap — React entry point.
 *
 * Provider hierarchy (outer → inner):
 *   StrictMode   — enables additional React development warnings
 *   BrowserRouter — provides history-based client-side routing
 *   AuthProvider  — manages the JWT session and exposes it via useAuth()
 *   App           — declares all route definitions
 *
 * BrowserRouter must wrap AuthProvider (not the other way around) because
 * AuthProvider's logout callback calls navigate(), which requires a Router
 * to already be in the tree.
 */

import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'

import './index.css'
import { AuthProvider } from './auth/AuthContext.tsx'
import App from './App.tsx'

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <BrowserRouter>
      <AuthProvider>
        <App />
      </AuthProvider>
    </BrowserRouter>
  </StrictMode>,
)
