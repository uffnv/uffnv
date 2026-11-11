    </div> <!-- End Content -->
</div> <!-- End Flex -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
    const IDLE_LIMIT = <?= (int)(defined('ADMIN_SESSION_IDLE_LIMIT') ? ADMIN_SESSION_IDLE_LIMIT : 900) ?>;
    const CSRF_TOKEN = <?= json_encode(function_exists('getCsrfToken') ? getCsrfToken() : '') ?>;
    let remaining = <?= (int)($admin_session_remaining ?? 900) ?>;
    let lastServerPing = Date.now();
    let pingInFlight = false;
    let loggedOut = false;

    const timerEls = document.querySelectorAll('.admin-session-timer');

    function formatTime(sec) {
        const s = Math.max(0, sec);
        const m = Math.floor(s / 60);
        const r = s % 60;
        return String(m).padStart(2, '0') + ':' + String(r).padStart(2, '0');
    }

    function updateTimerUI() {
        const formatted = formatTime(remaining);
        timerEls.forEach(function(el) {
            el.textContent = formatted;
            if (remaining <= 60) {
                el.classList.remove('text-warning', 'border-warning');
                el.classList.add('text-danger', 'border-danger', 'fw-bolder');
            } else {
                el.classList.add('text-warning', 'border-warning');
                el.classList.remove('text-danger', 'border-danger', 'fw-bolder');
            }
        });
    }

    function lockScreenAndExit(reason) {
        if (loggedOut) return;
        loggedOut = true;
        try { localStorage.setItem('uffnv_admin_logout', String(Date.now())); } catch (e) {}

        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;z-index:999999;background:rgba(18,0,36,0.97);backdrop-filter:blur(12px);color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:24px;';
        overlay.innerHTML = '<div class="d-flex align-items-center justify-content-center mb-3 border border-3 border-dark" style="width:68px;height:68px;background:#FCE300;color:#000;box-shadow:6px 6px 0 #bc13fe;"><i class="bi bi-shield-lock-fill fs-2"></i></div>'
            + '<h4 class="street-font mb-2">Сессия администратора завершена</h4>'
            + '<p class="text-white-50 mb-0">Вы были автоматически отключены из соображений безопасности.</p>';
        document.body.appendChild(overlay);

        window.location.replace('/admin/logout.php?reason=' + encodeURIComponent(reason || 'timeout'));
    }

    function onUserActivity() {
        if (loggedOut) return;
        remaining = IDLE_LIMIT;
        updateTimerUI();

        const now = Date.now();
        if (!pingInFlight && CSRF_TOKEN && (now - lastServerPing) >= 45000) {
            pingInFlight = true;
            lastServerPing = now;
            const fd = new FormData();
            fd.append('csrf_token', CSRF_TOKEN);
            fetch('/admin/session_ping.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) {
                if (r.status === 401) {
                    lockScreenAndExit('timeout');
                    return null;
                }
                return r.json();
            })
            .then(function(data) {
                if (data && data.expired) lockScreenAndExit('timeout');
            })
            .catch(function() {})
            .finally(function() { pingInFlight = false; });
        }
    }

    ['mousedown', 'keydown', 'touchstart', 'scroll'].forEach(function(evt) {
        window.addEventListener(evt, onUserActivity, { passive: true });
    });

    window.addEventListener('storage', function(e) {
        if (e.key === 'uffnv_admin_logout') {
            lockScreenAndExit('timeout');
        }
    });

    document.querySelectorAll('a[href*="logout.php"]').forEach(function(link) {
        link.addEventListener('click', function() {
            try { localStorage.setItem('uffnv_admin_logout', String(Date.now())); } catch (e) {}
        });
    });

    window.addEventListener('pageshow', function(e) {
        if (e.persisted) {
            window.location.reload();
        }
    });

    updateTimerUI();
    setInterval(function() {
        if (loggedOut) return;
        remaining -= 1;
        updateTimerUI();
        if (remaining <= 0) {
            lockScreenAndExit('timeout');
        }
    }, 1000);
})();
</script>
</body>
</html>
