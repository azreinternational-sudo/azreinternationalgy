<?php
auth_logout();
flash_set('info', 'Signed out.');
redirect('/admin/login');
