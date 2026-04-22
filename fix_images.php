<?php
$urls = [
    'https://images.unsplash.com/photo-1617531653332-bd46c24f2068?auto=format&fit=crop&q=80&w=1600' => 'https://upload.wikimedia.org/wikipedia/commons/7/77/BMW_i4_M50_1X7A6854.jpg',
    'https://images.unsplash.com/photo-1555215695-3004980ad54e?auto=format&fit=crop&q=80&w=1600' => 'https://upload.wikimedia.org/wikipedia/commons/e/ea/2021_BMW_M5_Competition_-_Front.jpg',
    'https://images.unsplash.com/photo-1580273916550-e323be2ae537?auto=format&fit=crop&q=80&w=1600' => 'https://upload.wikimedia.org/wikipedia/commons/e/ea/2021_BMW_M5_Competition_-_Front.jpg',
    'https://images.unsplash.com/photo-1603386329225-868f9b1ee6c9?auto=format&fit=crop&q=80&w=1600' => 'https://upload.wikimedia.org/wikipedia/commons/e/e1/BMW_M4_Competition_Coupe_%28G82%29_-_Front.jpg',
    'https://images.unsplash.com/photo-1596700072970-13655517173e?auto=format&fit=crop&q=80&w=1600' => 'https://upload.wikimedia.org/wikipedia/commons/9/90/BMW_S1000_RR_01.jpg',
    'https://images.unsplash.com/photo-1621359900280-4c644ef19854?auto=format&fit=crop&q=80&w=1600' => 'https://upload.wikimedia.org/wikipedia/commons/f/f6/BMW_R_1250_GS_Adventure.jpg'
];

foreach ($urls as $old => $new) {
    echo "Updating: $old\n";
    App\Models\ProductImage::where('path', $old)->update(['path' => $new]);
}

echo "DB Images fixed.\n";
