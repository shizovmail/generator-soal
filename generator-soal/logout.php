<?php
require_once __DIR__ . '/config.php';
do_logout();
header('Location: ' . base_url('index.php'));
