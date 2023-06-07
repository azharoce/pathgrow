<?php

Route::get('demo', function() {
    echo 'Hello from the demo package!';
});


Route::get('demo-view', function () {
    return view('demo::index');
});

