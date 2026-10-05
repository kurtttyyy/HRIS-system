<style>
.matrix-employee-modal {
 --employee-accent:#059669; --employee-accent-deep:#047857;
 --employee-accent-soft:#ecfdf5; --employee-accent-border:#a7f3d0;
 --employee-profile-tint:#ecfdf5; --employee-decoration:rgba(16,185,129,.12);
 --employee-canvas:#f8fafc; --employee-surface:#fff; --employee-soft:#f1f5f9;
 --employee-border:#e2e8f0; --employee-heading:#0f172a; --employee-text:#1e293b; --employee-muted:#64748b;
}
html[data-theme="dark"] .matrix-employee-modal {
 --employee-accent:#059669; --employee-accent-deep:#6ee7b7;
 --employee-accent-soft:#064e3b; --employee-accent-border:#065f46;
 --employee-profile-tint:#102c2b; --employee-decoration:rgba(52,211,153,.10);
 --employee-canvas:#0f172a; --employee-surface:#111c30; --employee-soft:#172033;
 --employee-border:#334155; --employee-heading:#f8fafc; --employee-text:#e2e8f0; --employee-muted:#94a3b8;
}
html[data-theme="dark"] .matrix-employee-modal .matrix-employee-eyebrow { color:#6ee7b7; }
html[data-theme="dark"] .matrix-employee-modal .matrix-employee-card { border-color:#334155; }

.matrix-employee-modal { background:rgba(15,23,42,.55); backdrop-filter:blur(9px); perspective:1400px; }
.matrix-employee-modal.is-open { animation:employee-backdrop .25s ease both; }
.matrix-employee-modal .matrix-employee-card { width:min(1120px,100%); border:1px solid rgba(255,255,255,.8); border-radius:24px; background:var(--employee-canvas); box-shadow:0 40px 100px rgba(2,6,23,.33); }
.matrix-employee-modal.is-open .matrix-employee-card { animation:employee-arrive .55s cubic-bezier(.16,1,.3,1) both; }
.matrix-employee-modal .matrix-employee-card-header { padding:22px 28px; background:var(--employee-surface); color:var(--employee-heading); border-bottom:1px solid var(--employee-border); }
.matrix-employee-eyebrow { color:var(--employee-accent); font-size:10px; font-weight:800; letter-spacing:.18em; text-transform:uppercase; margin-bottom:4px; }
.matrix-employee-modal .matrix-employee-card-title { font-size:23px; letter-spacing:-.04em; }
.matrix-employee-modal .matrix-employee-close { width:38px; height:38px; border-radius:12px; background:var(--employee-soft); color:var(--employee-muted); transition:transform .2s,background .2s; }
.matrix-employee-modal .matrix-employee-close:hover { transform:rotate(90deg); background:var(--employee-accent-soft); color:var(--employee-accent-deep); }
.matrix-employee-modal .matrix-employee-card-body { grid-template-columns:250px minmax(0,1fr); padding:0; gap:0; }
.matrix-employee-profile { position:relative; padding:28px 22px; overflow:hidden; background:linear-gradient(155deg,var(--employee-profile-tint),var(--employee-canvas) 65%); border-right:1px solid var(--employee-border); }
.matrix-employee-profile::before { content:''; position:absolute; width:180px; height:180px; top:-100px; left:-80px; border:30px solid var(--employee-decoration); border-radius:50%; pointer-events:none; }
.matrix-employee-modal .matrix-employee-avatar { position:relative; width:112px; aspect-ratio:1; margin:0 auto 18px; border-radius:28px; border:4px solid var(--employee-surface); background:linear-gradient(135deg,#059669,#064e3b); color:#fff; font-size:36px; box-shadow:0 12px 28px rgba(5,150,105,.19); transition:transform .3s,box-shadow .3s; }
.matrix-employee-modal .matrix-employee-avatar:hover { transform:perspective(600px) rotateY(-9deg) rotateX(5deg) translateY(-4px); box-shadow:8px 18px 35px rgba(5,150,105,.25); }
.matrix-employee-profile-name { text-align:center; font-size:19px; font-weight:750; line-height:1.4; color:var(--employee-heading); overflow-wrap:anywhere; }
.matrix-employee-profile-role { text-align:center; font-size:12px; color:var(--employee-muted); margin:6px 0 24px; }
.matrix-employee-tabs { display:grid; gap:7px; }
.matrix-employee-tab { display:flex; align-items:center; gap:10px; text-align:left; padding:12px; border:1px solid transparent; border-radius:12px; color:var(--employee-muted); font-size:12px; font-weight:600; transition:background .2s,transform .2s; }
.matrix-employee-tab span { display:grid; place-items:center; width:25px; height:25px; border-radius:8px; background:var(--employee-border); font-size:10px; }
.matrix-employee-tab:hover { background:var(--employee-surface); transform:translateX(3px); }
.matrix-employee-tab[aria-selected="true"] { background:var(--employee-surface); color:var(--employee-accent-deep); border-color:var(--employee-accent-soft); box-shadow:0 4px 12px rgba(2,6,23,.02); }
.matrix-employee-tab[aria-selected="true"] span { background:var(--employee-accent); color:#fff; }
.matrix-employee-modal .matrix-employee-fields { padding:28px; }
.matrix-employee-modal .matrix-employee-section { grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
.matrix-employee-modal .matrix-employee-section.is-active { animation:employee-section .35s ease both; }
.matrix-employee-modal .matrix-employee-section-title { grid-column:1/-1; color:var(--employee-heading); text-transform:none; letter-spacing:-.02em; font-size:19px; border:0; margin-bottom:8px; }
.matrix-employee-modal .matrix-employee-field { display:block; padding:13px 15px; border:1px solid var(--employee-border); border-radius:13px; background:var(--employee-surface); transition:border-color .2s,box-shadow .2s,transform .2s; min-width:0; }
.matrix-employee-modal .matrix-employee-field:hover { border-color:var(--employee-accent-border); box-shadow:0 5px 16px rgba(2,6,23,.03); transform:translateY(-2px); }
.matrix-employee-modal .matrix-employee-label { color:var(--employee-muted); font-size:10px; font-weight:650; letter-spacing:.06em; text-transform:uppercase; margin-bottom:6px; }
.matrix-employee-modal .matrix-employee-value { padding:0; border:0; background:none; min-height:0; font-size:14px; font-weight:550; color:var(--employee-text); overflow-wrap:anywhere; }
.matrix-employee-modal .matrix-employee-field:has(#matrix-employee-address) { grid-column:1/-1; }
.matrix-employee-modal .matrix-employee-carousel-footer { padding-top:18px; margin-top:8px; }
.matrix-employee-modal .matrix-employee-carousel-status { font-size:11px; color:var(--employee-muted); }
.matrix-employee-modal .matrix-employee-carousel-button { border-radius:10px; border-color:var(--employee-border); color:var(--employee-muted); padding:10px 17px; font-size:12px; transition:transform .2s,background .2s; }
.matrix-employee-modal #matrix-employee-next-section { background:var(--employee-accent); border-color:var(--employee-accent); color:#fff; box-shadow:0 4px 10px rgba(5,150,105,.15); }
.matrix-employee-modal .matrix-employee-carousel-button:hover:not(:disabled) { transform:translateY(-2px); }
.matrix-employee-modal button:focus-visible { outline:3px solid #6ee7b7; outline-offset:3px; }
.matrix-employee-modal .matrix-employee-section.is-active .matrix-employee-field { animation:employee-field .45s ease both; }
.matrix-employee-modal .matrix-employee-section.is-active .matrix-employee-field:nth-child(3n) { animation-delay:45ms; }
.matrix-employee-modal .matrix-employee-section.is-active .matrix-employee-field:nth-child(3n + 1) { animation-delay:90ms; }
.matrix-employee-progress { height:3px; background:var(--employee-border); border-radius:4px; overflow:hidden; margin-top:8px; width:90px; }
.matrix-employee-progress span { display:block; height:100%; background:linear-gradient(90deg,#059669,#34d399); width:25%; transition:width .35s ease; }
@keyframes employee-field { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
@keyframes employee-arrive { from { opacity:0; transform:translateY(32px) rotateX(7deg) scale(.96); } to { opacity:1; transform:translateY(0) rotateX(0) scale(1); } }
@keyframes employee-backdrop { from { opacity:0; } to { opacity:1; } }
@keyframes employee-section { from { opacity:0; transform:translateX(12px); } to { opacity:1; transform:translateX(0); } }
@media(max-width:720px) {
 .matrix-employee-modal .matrix-employee-card { max-height:94dvh; border-radius:18px; }
 .matrix-employee-modal .matrix-employee-card-body { grid-template-columns:1fr; }
 .matrix-employee-modal .matrix-employee-card-header { padding:16px 20px; }
 .matrix-employee-profile { padding:20px; border-right:0; border-bottom:1px solid var(--employee-border); }
 .matrix-employee-modal .matrix-employee-avatar { width:72px; border-radius:20px; margin-bottom:10px; font-size:24px; }
 .matrix-employee-profile-name { font-size:17px; }
 .matrix-employee-profile-role { margin-bottom:16px; }
 .matrix-employee-tabs { grid-template-columns:repeat(4,minmax(0,1fr)); gap:4px; }
 .matrix-employee-tab { padding:8px 3px; justify-content:center; font-size:10px; }
 .matrix-employee-tab span { display:none; }
 .matrix-employee-modal .matrix-employee-fields { padding:20px; }
}
@media(max-width:420px) { .matrix-employee-modal .matrix-employee-section { grid-template-columns:1fr; } }
@media(prefers-reduced-motion:reduce) { .matrix-employee-modal *, .matrix-employee-modal.is-open { animation:none!important; transition:none!important; transform:none!important; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('matrix-employee-modal');
  if (!modal) return;
  let returnFocus = null;
  let wasOpen = false;
  new MutationObserver(() => {
    const open = modal.classList.contains('is-open');
    if (open && !wasOpen) {
      returnFocus = document.activeElement;
      modal.querySelector('.matrix-employee-close').focus({ preventScroll: true });
    } else if (!open && wasOpen && returnFocus?.isConnected) {
      returnFocus.focus({ preventScroll: true });
    }
    wasOpen = open;
  }).observe(modal, { attributes: true, attributeFilter: ['class'] });
  modal.addEventListener('keydown', event => {
    if (event.key !== 'Tab') return;
    const buttons = Array.from(modal.querySelectorAll('button:not(:disabled)')).filter(button => button.getClientRects().length);
    const first = buttons[0], last = buttons[buttons.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault(); last?.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault(); first?.focus();
    }
  });
});
</script>
