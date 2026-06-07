<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
body.staff-app {
    font-family: 'Inter', 'Segoe UI', sans-serif;
    background: #f0f4f8;
    min-height: 100vh;
}

.staff-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 260px;
    height: 100vh;
    background: linear-gradient(180deg, var(--primary) 0%, var(--primaryDark) 100%);
    color: #fff;
    padding: 24px 18px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    z-index: 300;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
    box-shadow: 4px 0 24px rgba(0,0,0,0.12);
}

.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    z-index: 250;
}

body.sidebar-open .sidebar-overlay {
    display: block;
}

@media (min-width: 769px) {
    .staff-sidebar {
        transform: translateX(0);
    }

    body.sidebar-collapsed .staff-sidebar {
        transform: translateX(-100%);
    }

    body.sidebar-collapsed .staff-main {
        margin-left: 0;
    }
}

.staff-sidebar .brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 14px;
    margin-bottom: 28px;
}

.staff-sidebar .brand img {
    width: 48px;
    height: 48px;
    object-fit: contain;
    background: rgba(255,255,255,0.12);
    border-radius: 10px;
    padding: 4px;
}

.staff-sidebar .brand-title {
    font-size: 13px;
    font-weight: 800;
    line-height: 1.3;
    letter-spacing: 0.3px;
}

.staff-sidebar .brand-sub {
    font-size: 11px;
    opacity: 0.8;
    margin-top: 2px;
}

.staff-nav {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.staff-nav a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    color: rgba(255,255,255,0.9);
    text-decoration: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.2s, color 0.2s;
}

.staff-nav a:hover {
    background: rgba(255,255,255,0.12);
}

.staff-nav a.active {
    background: #fff;
    color: var(--primary);
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.staff-nav .nav-icon {
    width: 20px;
    text-align: center;
    font-size: 16px;
}

.staff-sidebar .user-panel {
    padding: 14px;
    background: rgba(0,0,0,0.15);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 12px;
    font-size: 12px;
}

.staff-sidebar .user-panel strong {
    display: block;
    font-size: 14px;
    margin: 4px 0 2px;
}

.staff-main {
    margin-left: 260px;
    min-height: 100vh;
    transition: margin-left 0.3s ease;
}

.staff-header {
    position: sticky;
    top: 0;
    z-index: 200;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 28px;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}

.staff-header .header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.staff-header .menu-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    cursor: pointer;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    color: var(--primary);
    padding: 6px 10px;
    line-height: 1;
}

.staff-header .system-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--primary);
    letter-spacing: 0.5px;
}

.staff-header .header-user {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
}

.staff-header .avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), var(--primary));
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 15px;
}

.staff-header .user-name {
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.2;
}

.staff-header .user-role {
    font-size: 11px;
    color: #64748b;
    font-weight: 600;
}

.staff-header .header-emblem {
    height: 36px;
    object-fit: contain;
}

.staff-header .header-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.btn-logout {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    color: #64748b;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.2s, color 0.2s, border-color 0.2s;
}

.btn-logout:hover {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
}

.staff-nav .nav-logout {
    margin-top: 8px;
    border-top: 1px solid rgba(255, 255, 255, 0.15);
    padding-top: 14px;
    color: rgba(255, 255, 255, 0.85);
}

.staff-nav .nav-logout:hover {
    background: rgba(220, 38, 38, 0.25);
    color: #fff;
}

.staff-content {
    padding: 28px;
    max-width: 1400px;
}

.dash-hero {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 20px;
    padding: 28px 30px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primaryDark) 100%);
    border-radius: 18px;
    color: #fff;
    margin-bottom: 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.12);
}

.dash-hero h2 {
    margin: 0 0 6px;
    font-size: 26px;
    font-weight: 800;
}

.dash-hero .location {
    margin: 0;
    font-size: 14px;
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.dash-hero .location span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.dash-hero .location strong {
    font-weight: 700;
}

.dash-hero .hero-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.dash-hero .hero-btn {
    padding: 10px 18px;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.3);
    color: #fff;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.2s;
}

.dash-hero .hero-btn:hover {
    background: rgba(255,255,255,0.25);
}

.dash-hero .hero-btn.primary {
    background: #fff;
    color: var(--primary);
    border-color: #fff;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 24px;
}

.kpi-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px 22px;
    border: 1px solid #e8edf2;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    display: flex;
    gap: 16px;
    align-items: flex-start;
    transition: transform 0.2s, box-shadow 0.2s;
}

.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}

.kpi-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}

.kpi-icon.nurse { background: rgba(22, 160, 133, 0.12); }
.kpi-icon.waiting { background: rgba(245, 158, 11, 0.12); }
.kpi-icon.doctor { background: rgba(59, 130, 246, 0.12); }
.kpi-icon.total { background: rgba(100, 116, 139, 0.12); }
.kpi-icon.pharmacy { background: rgba(124, 58, 237, 0.12); }
.kpi-icon.appointments { background: rgba(6, 182, 212, 0.12); }

.kpi-card h4 {
    margin: 0 0 4px;
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.kpi-card .kpi-value {
    font-size: 32px;
    font-weight: 800;
    color: var(--primary);
    line-height: 1;
}

.kpi-card .kpi-sub {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 6px;
    font-weight: 600;
}

.dash-panel {
    background: #fff;
    border-radius: 16px;
    padding: 22px 24px;
    border: 1px solid #e8edf2;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
}

.dash-panel-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
    padding-bottom: 14px;
    border-bottom: 1px solid #f1f5f9;
}

.dash-panel-head h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
}

.dash-panel-head .panel-tag {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    background: #f1f5f9;
    padding: 4px 10px;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.dash-charts {
    display: grid;
    grid-template-columns: 1fr 1fr 340px;
    gap: 20px;
}

.chart-wrap {
    height: 240px;
    position: relative;
}

.activity-list {
    list-style: none;
    margin: 0;
    padding: 0;
    max-height: 280px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--accent);
    flex-shrink: 0;
}

.activity-info {
    flex: 1;
    min-width: 0;
}

.activity-info strong {
    display: block;
    font-size: 13px;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.activity-info span {
    font-size: 11px;
    color: #94a3b8;
    font-weight: 600;
}

.activity-empty {
    text-align: center;
    padding: 40px 20px;
    color: #94a3b8;
}

.activity-empty .empty-icon {
    font-size: 36px;
    margin-bottom: 10px;
    opacity: 0.5;
}

.activity-empty p {
    margin: 0;
    font-size: 13px;
    font-weight: 600;
}

.section-title {
    margin: 0 0 20px;
    font-size: 20px;
    font-weight: 800;
    color: #1e293b;
}

.alert-success {
    padding: 12px 16px;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    border-radius: 10px;
    color: #047857;
    font-weight: 700;
    font-size: 13px;
    margin-bottom: 18px;
}

.alert-info {
    padding: 12px 16px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    color: #1d4ed8;
    font-weight: 700;
    font-size: 13px;
    margin-bottom: 18px;
}

.patients-panel .filterBar {
    background: #f8fafc;
    padding: 16px;
    border-radius: 12px;
    border: 1px solid #e8edf2;
    margin-bottom: 18px;
}

.patients-panel .filterBar input,
.patients-panel .filterBar select {
    margin-top: 0;
    flex: 1;
    min-width: 140px;
}

.referral-panel {
    margin-top: 12px;
    padding-top: 0;
    border-top: none;
}
.referral-panel h4 {
    margin: 0 0 6px;
}
.referral-panel .muted {
    margin: 0 0 8px;
    font-size: 12px;
}

#consultModal .modal-card.consult-form {
    width: 460px;
    max-width: 92vw;
    max-height: 88vh;
    overflow-y: auto;
    padding: 14px 16px;
}

.consult-form .consult-summary p {
    margin: 4px 0;
    font-size: 12px;
    line-height: 1.4;
}

.consult-form .consult-notes {
    background: #f8fafc;
    border: 1px solid #e8edf2;
    border-radius: 8px;
    padding: 8px 10px;
}

.consult-form form {
    margin-top: 10px;
}

.consult-form input,
.consult-form select,
.consult-form textarea {
    margin-top: 6px;
    padding: 8px 10px;
    font-size: 13px;
}

.consult-form textarea {
    min-height: 44px;
    resize: vertical;
}

.consult-form .btn {
    margin-top: 8px;
    padding: 8px 12px;
    font-size: 13px;
}

.consult-form .consult-expand {
    margin-top: 10px;
    padding-top: 8px;
    border-top: 1px solid #e8edf2;
}

.consult-form .consult-expand summary {
    cursor: pointer;
    font-weight: 700;
    font-size: 13px;
    color: var(--primary);
    padding: 4px 0;
    list-style-position: inside;
}

.consult-form .consult-expand[open] summary {
    margin-bottom: 8px;
}

.consult-form label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 8px 0;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
}

.consult-form label input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.vitals-form {
    margin-top: 20px;
    padding: 22px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
}

.vitals-form h3 {
    margin: 0 0 16px;
    font-size: 17px;
    font-weight: 700;
    color: var(--primary);
}

.vitals-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.vitals-grid input,
.vitals-form textarea {
    margin-top: 0;
}

.vitals-form textarea {
    grid-column: 1 / -1;
    min-height: 80px;
    resize: vertical;
}

.vitals-form .btn {
    margin-top: 14px;
    width: 100%;
    padding: 13px;
    font-size: 14px;
}

.progress-row {
    margin-bottom: 12px;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 6px;
    color: #334155;
}

.progress-track {
    height: 10px;
    background: #eef2f7;
    border-radius: 999px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, var(--accent), var(--primary));
}

.kpi-grid.wide {
    grid-template-columns: repeat(3, 1fr);
}

@media (max-width: 1100px) {
    .kpi-grid.wide { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .vitals-grid,
    .kpi-grid.wide { grid-template-columns: 1fr; }
}

.reports-hero .hero-btn {
    margin-top: 0;
}

.reports-grid {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.chart-box {
    height: 260px;
    position: relative;
}

.chart-box.tall {
    height: 300px;
}

.summary-table {
    width: 100%;
    border-collapse: collapse;
}

.summary-table th,
.summary-table td {
    padding: 11px 12px;
    border-bottom: 1px solid #eef2f7;
    text-align: left;
    font-size: 14px;
}

.summary-table th {
    background: #f8fafc;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.35px;
    color: #64748b;
}

.summary-table tr:last-child td {
    border-bottom: none;
}

.report-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    padding-bottom: 12px;
    border-bottom: 1px solid #eef2f7;
}

.report-card-head h3 {
    margin: 0;
    font-size: 18px;
    color: #1e293b;
}

.report-footer {
    margin-top: 10px;
    padding: 14px 16px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    font-size: 13px;
    color: #64748b;
    line-height: 1.5;
}

.reports-meta {
    text-align: right;
    font-size: 13px;
    opacity: 0.9;
}

.reports-meta strong {
    display: block;
    font-size: 15px;
    margin-bottom: 4px;
}

.reports-actions {
    display: flex;
    gap: 10px;
    margin-top: 14px;
    flex-wrap: wrap;
}

.btn.print {
    background: #fff;
    color: var(--primary);
    border: 1px solid rgba(255, 255, 255, 0.35);
}

@media print {
    .staff-sidebar,
    .sidebar-overlay,
    .staff-header,
    .menu-btn,
    .btn-logout,
    .reports-actions {
        display: none !important;
    }
    .staff-main {
        margin-left: 0 !important;
    }
    .staff-content {
        padding: 0;
    }
    .dash-hero {
        box-shadow: none;
    }
}

.announcement-item {
    padding: 16px;
    background: #f8fafc;
    border: 1px solid #e8edf2;
    border-radius: 12px;
    margin-top: 14px;
}

.announcement-item strong {
    color: var(--primary);
    font-size: 14px;
}

.announcement-item p {
    margin: 8px 0 0;
    font-size: 14px;
    color: #475569;
    line-height: 1.5;
}

.announcement-item .ann-meta {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 10px;
    font-size: 11px;
    color: #94a3b8;
    font-weight: 600;
}

.announcement-feed {
    max-height: 320px;
    overflow-y: auto;
}

.announcement-feed .announcement-item:first-child {
    margin-top: 0;
}

.announcement-item.emergency {
    background: #fef2f2;
    border-color: #fecaca;
    border-left: 4px solid #dc2626;
}

.badge.emergency {
    background: #dc2626;
    color: #fff;
}

.emergency-banner {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    margin-bottom: 20px;
    padding: 14px 18px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 12px;
    color: #991b1b;
}

.emergency-banner span {
    flex: 1;
    font-size: 13px;
    color: #b91c1c;
}

.emergency-triage-panel {
    margin-bottom: 24px;
    border: 1px solid #fecaca;
}

.emergency-tag {
    background: #dc2626 !important;
    color: #fff !important;
}

.emergency-card {
    margin-top: 16px;
    padding: 18px;
    background: #fff;
    border: 1px solid #fecaca;
    border-radius: 12px;
}

.emergency-card.resolved {
    border-color: #e2e8f0;
    background: #f8fafc;
}

.emergency-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}

.emergency-time {
    font-size: 12px;
    color: #94a3b8;
    font-weight: 600;
}

.emergency-details {
    margin: 10px 0 14px;
    font-size: 14px;
    color: #475569;
    line-height: 1.5;
}

.medi-alert-form,
.emergency-flag-form,
.emergency-panel {
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px solid #e8edf2;
}

.medi-alert-form label,
.emergency-flag-form h4,
.emergency-panel h4 {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
}

.medi-alert-form select,
.emergency-flag-form select,
.emergency-panel select,
.emergency-flag-form textarea,
.emergency-panel textarea {
    width: 100%;
    margin-bottom: 10px;
}

.medi-channels {
    display: flex;
    gap: 16px;
    margin: 10px 0 14px;
    font-size: 13px;
    font-weight: 600;
}

.ack-form {
    margin-top: 10px;
}

.emergency-history {
    margin-top: 18px;
    font-size: 13px;
}

.emergency-history summary {
    cursor: pointer;
    font-weight: 700;
    color: #64748b;
    margin-bottom: 10px;
}

.btn.emergency-btn {
    background: #dc2626;
    border-color: #dc2626;
}

.btn.emergency-btn:hover {
    background: #b91c1c;
    border-color: #b91c1c;
}

.alert-error {
    padding: 12px 16px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 10px;
    color: #b91c1c;
    font-weight: 700;
    font-size: 13px;
    margin-bottom: 18px;
}

.live-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 700;
    color: #059669;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    padding: 4px 10px;
    border-radius: 999px;
}

.live-badge .live-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10b981;
    animation: livePulse 1.5s ease-in-out infinite;
}

@keyframes livePulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.85); }
}

.announcements-layout {
    display: grid;
    grid-template-columns: 1.4fr 1fr;
    gap: 20px;
    align-items: start;
}

@media (max-width: 1100px) {
    .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .dash-charts { grid-template-columns: 1fr 1fr; }
    .dash-charts .activity-panel { grid-column: 1 / -1; }
    .reports-grid { grid-template-columns: 1fr; }
    .announcements-layout { grid-template-columns: 1fr; }
}


@media (max-width: 768px) {
    .staff-main {
        margin-left: 0;
    }

    body.sidebar-open .staff-sidebar {
        transform: translateX(0);
    }

    .staff-content {
        padding: 18px;
    }
    .kpi-grid,
    .dash-charts {
        grid-template-columns: 1fr;
    }
    .dash-hero {
        padding: 22px;
    }
    .dash-hero h2 {
        font-size: 22px;
    }
}
</style>
