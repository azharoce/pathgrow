<?php

Route::get('demo', function() {
    echo 'Hello from the demo package!';
});


Route::get('demo-view', function () {
    return view('demo::index');
});


git@github.com-personal:azharoce/monitoring-distributor.git
git remote add origin git@github.com:Lab-Informatika/laravel-demo-package.git

git remote add origin git@github.com-personal:azharoce/tbs-kit.git
