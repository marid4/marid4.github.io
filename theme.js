/*!
 * Script Toggle Dark/Light Mode
 * Menggunakan Bootstrap 5.3 data-bs-theme
 */

// 1. Cek apakah user pernah simpan pilihan tema sebelumnya
const getStoredTheme = () => localStorage.getItem('theme');
const setStoredTheme = theme => localStorage.setItem('theme', theme);

// 2. Fungsi untuk mengambil tema (Default: Light jika belum ada pilihan)
const getPreferredTheme = () => {
    const storedTheme = getStoredTheme();
    if (storedTheme) {
        return storedTheme;
    }
    return 'light'; // Default
};

// 3. Fungsi mengubah atribut HTML
const setTheme = theme => {
    document.documentElement.setAttribute('data-bs-theme', theme);
    
    // Ubah Ikon Tombol
    const btnIcon = document.getElementById('theme-icon');
    if(btnIcon) {
        if (theme === 'dark') {
            btnIcon.classList.remove('fa-moon');
            btnIcon.classList.add('fa-sun');
        } else {
            btnIcon.classList.remove('fa-sun');
            btnIcon.classList.add('fa-moon');
        }
    }
};

// 4. Jalankan saat awal load
setTheme(getPreferredTheme());

// 5. Fungsi yang dipanggil saat tombol diklik
function toggleTheme() {
    const current = document.documentElement.getAttribute('data-bs-theme');
    const newTheme = current === 'dark' ? 'light' : 'dark';
    setStoredTheme(newTheme);
    setTheme(newTheme);
}