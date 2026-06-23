<?php
auth_logout();
flash_set('info', 'You’ve been signed out.');
redirect('/');
