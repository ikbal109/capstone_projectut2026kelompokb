<?php
// File sementara untuk generate hash password
$admin   = password_hash('admin123', PASSWORD_DEFAULT);
$petugas = password_hash('petugas123', PASSWORD_DEFAULT);

echo "<h3>Hash Password</h3>";
echo "<p><strong>admin123</strong> → <br><code>$admin</code></p>";
echo "<p><strong>petugas123</strong> → <br><code>$petugas</code></p>";
echo "<hr>";
echo "<p>Copy hash di atas, lalu update ke database via phpMyAdmin.</p>";