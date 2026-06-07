<style>
:root{
 --primary: <?= $theme['primary'] ?>;
 --primaryDark: <?= $theme['primaryDark'] ?>;
 --sidebar: <?= $theme['sidebar'] ?>;
 --accent: <?= $theme['accent'] ?>;
 --bg:#f4f6f9;
}
body{margin:0;font-family:Segoe UI;background:var(--bg);}
.sidebar{position:fixed;top:0;left:0;width:240px;height:100%;background:var(--sidebar);color:#fff;padding:20px;transform:translateX(-100%);transition:.3s;z-index:200;}
.sidebar.active{transform:translateX(0);}
.sidebar a{display:block;padding:12px;margin:10px 0;color:#fff;text-decoration:none;border-radius:8px;}
.sidebar a:hover,.sidebar a.active{background:#fff;color:var(--primary);}
.header{display:flex;justify-content:space-between;align-items:center;padding:15px 20px;background:linear-gradient(90deg,var(--primary),var(--primaryDark));color:#fff;}
.menu-btn{font-size:22px;cursor:pointer;}
.content{padding:25px;}
.card{background:#fff;padding:20px;border-radius:14px;margin-bottom:20px;box-shadow:0 6px 15px rgba(0,0,0,.08);}
.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:15px;}
.stat{text-align:center;}
.stat h1{color:var(--primary);margin:10px 0 0;}
input,select,textarea{width:100%;padding:10px;margin-top:10px;border-radius:8px;border:1px solid #ccc;box-sizing:border-box;}
.btn{background:var(--accent);color:#fff;padding:10px 14px;border:none;border-radius:8px;cursor:pointer;font-weight:600;text-decoration:none;display:inline-block;}
.btn.secondary{background:#fff;border:1px solid #d7dde3;color:#111;}
.btn.small{padding:8px 10px;font-size:13px;}
.table-wrap{border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;margin-top:14px;}
.patients-table{width:100%;border-collapse:collapse;}
.patients-table th,.patients-table td{padding:12px 14px;border-bottom:1px solid #eef2f7;text-align:left;}
.patients-table thead th{background:#f8fafc;font-size:13px;text-transform:uppercase;}
.badge{display:inline-block;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:800;}
.badge.waiting{background:rgba(245,158,11,.15);color:#b45309;}
.badge.registered,.badge.completed{background:rgba(16,185,129,.15);color:#047857;}
.badge.nurse,.badge.doctor{background:rgba(59,130,246,.13);color:#1d4ed8;}
.badge.doctorwait,.badge.pharmacy{background:rgba(124,58,237,.12);color:#6d28d9;}
.badge.other{background:rgba(59,130,246,.12);color:#1d4ed8;}
.badge.referred{background:rgba(239,68,68,.12);color:#b91c1c;}
.action-cell{display:flex;gap:8px;flex-wrap:wrap;}
.muted,.smallMuted{color:#6b7280;font-weight:600;}
.formBox{display:none;margin-top:15px;}.formBox.show{display:block;}
.modal{position:fixed;inset:0;background:rgba(0,0,0,.45);display:none;align-items:center;justify-content:center;padding:20px;z-index:999;}
.modal.show{display:flex;}
.modal-card{width:560px;max-width:95vw;background:#fff;border-radius:16px;padding:18px;}
.filterBar{display:flex;gap:10px;flex-wrap:wrap;margin:15px 0;}
.chart-box{height:220px;}
.chart-box.tall{height:300px;}
.dashboardRow{display:grid;grid-template-columns:2fr 1fr;gap:20px;}
.chartRow{display:grid;grid-template-columns:1fr 1fr;gap:20px;}

/* Reports */
.reports-hero{
 display:flex;justify-content:space-between;align-items:flex-start;gap:20px;
 flex-wrap:wrap;margin-bottom:22px;padding:22px 24px;
 background:linear-gradient(135deg,var(--primary),var(--primaryDark));
 color:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.12);
}
.reports-hero h2{margin:0 0 6px;font-size:28px;}
.reports-hero p{margin:0;opacity:.92;line-height:1.5;}
.reports-meta{text-align:right;font-size:13px;opacity:.9;}
.reports-meta strong{display:block;font-size:15px;margin-bottom:4px;}
.reports-actions{display:flex;gap:10px;margin-top:14px;}
.btn.print{background:#fff;color:var(--primary);border:1px solid rgba(255,255,255,.35);}
.kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:22px;}
.kpi-card{
 background:#fff;border-radius:14px;padding:18px 18px 16px;
 border:1px solid #e5e7eb;box-shadow:0 4px 14px rgba(0,0,0,.05);
 display:flex;gap:14px;align-items:flex-start;
}
.kpi-icon{
 width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;
 font-size:22px;background:rgba(0,0,0,.04);flex-shrink:0;
}
.kpi-card h4{margin:0 0 4px;font-size:13px;color:#64748b;text-transform:uppercase;letter-spacing:.4px;}
.kpi-card .value{font-size:32px;font-weight:800;color:var(--primary);line-height:1.1;}
.kpi-card .sub{font-size:12px;color:#64748b;margin-top:6px;font-weight:600;}
.reports-grid{display:grid;grid-template-columns:1.2fr 1fr;gap:20px;margin-bottom:20px;}
.report-card-head{
 display:flex;justify-content:space-between;align-items:center;gap:10px;
 margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid #eef2f7;
}
.report-card-head h3{margin:0;font-size:18px;}
.summary-table{width:100%;border-collapse:collapse;}
.summary-table th,.summary-table td{padding:11px 12px;border-bottom:1px solid #eef2f7;text-align:left;font-size:14px;}
.summary-table th{background:#f8fafc;font-size:12px;text-transform:uppercase;letter-spacing:.35px;color:#64748b;}
.summary-table tr:last-child td{border-bottom:none;}
.progress-row{margin-bottom:12px;}
.progress-label{display:flex;justify-content:space-between;font-size:13px;font-weight:700;margin-bottom:6px;}
.progress-track{height:10px;background:#eef2f7;border-radius:999px;overflow:hidden;}
.progress-fill{height:100%;border-radius:999px;background:linear-gradient(90deg,var(--accent),var(--primary));}
.report-footer{
 margin-top:10px;padding:14px 16px;background:#f8fafc;border:1px solid #e5e7eb;
 border-radius:12px;font-size:13px;color:#64748b;
}
@media print{
 .sidebar,.header,.menu-btn,.reports-actions,.filterBar,.btn{display:none!important;}
 .content{padding:0;}
 body{background:#fff;}
 .reports-hero{box-shadow:none;}
}
@media(max-width:1000px){.grid,.chartRow,.dashboardRow,.kpi-grid,.reports-grid{grid-template-columns:1fr;}}
</style>
