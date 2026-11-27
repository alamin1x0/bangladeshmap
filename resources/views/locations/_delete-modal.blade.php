<div class="dm" id="delete-modal" hidden role="dialog" aria-modal="true" aria-labelledby="dm-title">
    <div class="dm-card">
        <div class="dm-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
        </div>
        <h2 id="dm-title">Delete?</h2>
        <p id="dm-text"></p>
        <div class="dm-impact" id="dm-impact" hidden></div>
        <form method="post" id="dm-form">
            @csrf @method('DELETE')
            <div class="dm-buttons">
                <button type="button" class="dm-cancel" data-dm-close>Cancel</button>
                <button type="submit" class="dm-confirm" id="dm-confirm">Yes, delete</button>
            </div>
        </form>
    </div>
</div>
<style>
    .dm[hidden]{display:none}
    .dm{position:fixed;inset:0;z-index:120;display:grid;place-items:center;padding:20px;background:#071127b8;backdrop-filter:blur(6px)}
    .dm-card{width:min(420px,100%);padding:28px 26px 22px;border:1px solid var(--line);border-radius:18px;background:var(--surface);text-align:center;box-shadow:0 25px 80px #0008;animation:dm-pop .16s ease-out}
    @keyframes dm-pop{from{transform:scale(.95);opacity:0}to{transform:none;opacity:1}}
    .dm-icon{display:grid;place-items:center;width:54px;height:54px;margin:0 auto 14px;border-radius:50%;background:#fff0f0;color:#d92d20}
    .dm-icon svg{width:26px;height:26px}
    .dm-card h2{margin:0 0 8px;font-size:19px;color:var(--ink)}
    .dm-card p{margin:0;color:var(--muted);font-size:13.5px;line-height:1.55}
    .dm-card p strong{color:var(--ink)}
    .dm-impact{margin-top:14px;padding:10px 12px;border-radius:10px;background:#fff6e5;color:#9a5b00;font-size:12.5px;font-weight:600;text-align:left}
    [data-theme="dark"] .dm-impact{background:#3a2a0a;color:#f4c162}
    .dm-buttons{display:flex;gap:10px;margin-top:20px}
    .dm-buttons button{flex:1;padding:11px 14px;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;border:1px solid var(--line)}
    .dm-cancel{background:var(--surface);color:var(--ink)}
    .dm-confirm{background:#d92d20;border-color:#d92d20!important;color:#fff}
    .dm-confirm:hover{background:#b42318}
    .dm-confirm[disabled]{opacity:.6;cursor:wait}
</style>
<script nonce="{{ Vite::cspNonce() }}">
    (function () {
        var modal = document.getElementById('delete-modal');
        if (!modal) return;
        var form = document.getElementById('dm-form'), btn = document.getElementById('dm-confirm');
        function close() { modal.hidden = true; btn.disabled = false; btn.textContent = 'Yes, delete'; }
        window.openDeleteModal = function (opts) {
            form.action = opts.action;
            document.getElementById('dm-title').textContent = 'Delete ' + opts.kind + '?';
            document.getElementById('dm-text').innerHTML = '';
            var strong = document.createElement('strong'); strong.textContent = opts.name;
            document.getElementById('dm-text').append('You are about to delete ', strong, '. This cannot be undone.');
            var impact = document.getElementById('dm-impact');
            impact.hidden = !opts.impact; impact.textContent = opts.impact || '';
            modal.hidden = false;
            btn.focus();
        };
        document.addEventListener('click', function (e) {
            var t = e.target.closest('[data-delete-url]');
            if (t) { e.preventDefault(); openDeleteModal({ action: t.dataset.deleteUrl, kind: t.dataset.deleteKind, name: t.dataset.deleteName, impact: t.dataset.deleteImpact }); }
            if (e.target.closest('[data-dm-close]') || e.target === modal) close();
        });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) close(); });
        form.addEventListener('submit', function () { btn.disabled = true; btn.textContent = 'Deleting…'; });
    })();
</script>
