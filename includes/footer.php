<?php
// includes/footer.php
?>
<footer class="footer">
    <div class="footer-content">
        <div class="footer-section">
            <h3>Tentang Aplikasi</h3>
            <p>Aplikasi Tabungan Siswa adalah sistem manajemen tabungan untuk siswa yang memudahkan pencatatan setoran dan penarikan tabungan secara digital.</p>
        </div>
        
        <div class="footer-section">
            <h3>Kontak</h3>
            <p>📞 (021) 1234-5678</p>
            <p>✉️ info@tabungansiswa.sch.id</p>
            <p>🏫 Jl. Pendidikan No. 123, Jakarta</p>
        </div>
        
        <div class="footer-section">
            <h3>Jam Layanan</h3>
            <p>Senin - Jumat: 07.00 - 15.00 WIB</p>
            <p>Sabtu: 07.00 - 12.00 WIB</p>
            <p>Minggu & Libur: Tutup</p>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> Aplikasi Tabungan Siswa. All rights reserved.</p>
        <p>Developed by <a href="#">Your Name</a></p>
    </div>
</footer>

<style>
.footer {
    background: #2d3748;
    color: #fff;
    margin-top: 50px;
    padding: 40px 0 20px;
    border-radius: 10px 10px 0 0;
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    padding: 0 20px;
}

.footer-section h3 {
    color: #48bb78;
    margin-bottom: 15px;
    font-size: 1.2em;
}

.footer-section p {
    line-height: 1.8;
    color: #cbd5e0;
    margin-bottom: 10px;
}

.footer-section a {
    color: #48bb78;
    text-decoration: none;
}

.footer-section a:hover {
    text-decoration: underline;
}

.footer-bottom {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #4a5568;
    color: #a0aec0;
}

.footer-bottom a {
    color: #48bb78;
    text-decoration: none;
}

@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
        text-align: center;
    }
}
</style>