<?php

require_once "globals.php";
require_once "include/mysql.php";
?>
<head>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <form method="post">
        <h1> Gate Assignment Generator </h1>
        <label><input type="checkbox" name="skip_assigned" <?php if (isset($_POST["skip_assigned"])) echo "checked";?>> Skip already assigned stands </label>
        <input type="submit" value="Start">
        <div class="log">
            Output log: <textarea readonly><?
                if ($_SERVER["REQUEST_METHOD"] == "POST") {
                    require_once "core.php";

                    $skip_assigned = isset($_POST["skip_assigned"]);

                    main($skip_assigned);
                }
            ?>
            </textarea>
        </div>
    </form>
    <input type="text" value="" id="search" placeholder="Search">
    <?php include "views/overview.php"; ?>
    <script>
        document.getElementById("search").addEventListener("keyup", function(){
            v = document.getElementById("search").value;

            if (v) 
            {
                all = document.querySelectorAll('div[cs]');
                for (const element of all) {
                    element.style.filter = "grayscale(1)"
                }
                matching = document.querySelectorAll('div[cs^='+v+']');
                for (const element of matching) {
                    element.style.filter = ""
                }
                matching2 = document.querySelectorAll('div[cs2^='+v+']');
                for (const element of matching2) {
                    element.style.filter = ""
                }
            }
            else
            {
                all = document.querySelectorAll('div[cs]');
                for (const element of all) {
                    element.style.filter = "grayscale(0)"
                }
            }
        });
        all = document.querySelectorAll('div[cs]');
        for (const element of all) {
            element.style.filter = "grayscale(0)"
        }
    </script>
</body>