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
    <?php include "views/overview.php"; ?>
</body>