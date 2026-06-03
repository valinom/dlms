/* =============================================
   DLMS — Sidebar / Header JS
   ============================================= */
(function () {
    const menuBtn = document.getElementById('menuBtn');
    const sidebar = document.getElementById('sidebar');

    if (!menuBtn || !sidebar) return;

    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('show');
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    }

    menuBtn.addEventListener('click', () => {
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });

    overlay.addEventListener('click', closeSidebar);

    // Close on link click (mobile)
    sidebar.querySelectorAll('a').forEach(a =>
        a.addEventListener('click', () => {
            if (window.innerWidth <= 768) closeSidebar();
        })
    );
})();
