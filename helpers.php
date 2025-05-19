<?php
function show_errors($errors) {
    if ($errors) {
        echo '<div class="alert alert-danger"><ul>';
        foreach ($errors as $err) echo '<li>' . htmlspecialchars($err) . '</li>';
        echo '</ul></div>';
    }
}
?>
