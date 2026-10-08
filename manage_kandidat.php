<?php
require 'config.php';
checkAdmin();
header('Location: admin.php', true, 303);
exit;
