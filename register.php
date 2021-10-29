<?php
    require_once("php/page.class.php");
    $page = new Page(0);
    if ($page->getUser()->getUsertype() > 0) { header("Location: /user.php"); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $page->displayHead(); ?>
    <link rel='stylesheet' href='style/css/register.css'>
    <title>getwhisky registration</title>
</head>
<body>
    <header>
        <?php echo $page->displayHeader(); ?>
    </header>
    <?php 
                echo "Session userid: ".$_SESSION['userid'];
                echo "<br>";
                echo "page userid: ".$page->getUser()->getUserid();
            ?>
    <main>
        <form method="post" action="reguser.php" class="register_form" id="regform">
            <div class="form-header">
                <h3>
                    getwhisky sign up
                </h3>
                <p>
                    sign up for an account to unlock features like order history!
                </p>
            </div>

            

            <div class="input-container-100">
                <i class="input-icon far fa-user"></i>
                <input class="form-item" type="text" id="username" name="username" placeholder="username" required />
            </div>
            <div class="input-container-50">
                <input class="form-item-50" type="text" id="firstname" name="firstname" placeholder="first name" required />
                <input class="form-item-50" type="text" id="surname" name="surname" placeholder="last name" required />

            </div>
            <div class="input-container-100">
                <i class="input-icon far fa-envelope"></i>
                <input class="form-item" type="email" id="email" name="email" placeholder="email address" required />
            </div>

            <div class="input-container-100">
                <i class="input-icon far fa-calendar-alt"></i>
                <input class="form-item" type="date" id="dob" name="dob" required/>
            </div>

            <div class="input-container-100">
                <i class="input-icon fas fa-lock"></i>
                <input class="form-item" type="password" id="userpass" name="userpass" placeholder="password" required />
            </div>

            <div class="submit">
                <button type="submit" id="submitbutton" name="submitbutton">Create Account</button>
            </div>
        </form>
    </main>
</body>
<script src="js/user-registration-form.js"></script>
<script>
    'use strict';
    document.onreadystatechange = function() {
        if(document.readyState==="complete") {
            new Form("regform");
        }
    }
</script>
</html>