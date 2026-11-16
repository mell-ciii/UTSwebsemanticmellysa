<?php
// Mencegah listing direktori; file gambar produk disimpan di folder ini.
http_response_code(403);
exit('Forbidden');
