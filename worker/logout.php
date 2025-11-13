<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

worker_logout();

redirect('login.php', 'Has cerrado sesión.', 'success');