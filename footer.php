</div> <!-- Tutup .main-content -->

<!-- Script JavaScript dengan LocalStorage -->
<script>
    function toggleSidebar() {
        // Toggle class di body
        document.body.classList.toggle('sidebar-hidden');
        
        // Simpan status ke localStorage browser
        if (document.body.classList.contains('sidebar-hidden')) {
            localStorage.setItem('sidebarState', 'hidden');
        } else {
            localStorage.setItem('sidebarState', 'visible');
        }
    }

    // Cek status saat halaman pertama kali dimuat / direfresh
    window.addEventListener('DOMContentLoaded', (event) => {
        const savedState = localStorage.getItem('sidebarState');
        if (savedState === 'hidden') {
            document.body.classList.add('sidebar-hidden');
        }
    });
</script>

</body>
</html>