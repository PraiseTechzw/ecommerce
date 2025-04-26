<?php
function showToast($message, $type = 'success') {
    echo "<div class='toast toast-$type' id='toast'>
            <div class='toast-content'>
                <i class='toast-icon'></i>
                <div class='toast-message'>$message</div>
            </div>
            <div class='toast-progress'></div>
          </div>";
}
?> 