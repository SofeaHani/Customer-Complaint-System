<?php
session_start();

if (isset($_SESSION['new_complaint']) && $_SESSION['new_complaint'] === true) {
    echo 'new';
    $_SESSION['new_complaint'] = false; // Reset after notifying
} else {
    echo 'none';
}
