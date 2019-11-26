<html>
    <head>
        <meta charset="UTF-8">
        <title>Login</title>
        <link href="<?php echo BASE_URL; ?>/assets/css/login.css" rel="stylesheet" />
    </head>
    <body>
        <div class="loginarea">
            <div class="logoMygle">
                <img src='assets/images/defaultLogo.png'>
            </div>
        	<form method="POST">
        		<input type="email" name="email" placeholder="Email" />

        		<input type="password" name="password" placeholder="Senha" />

        		<input type="submit" value="Entrar" /><br/>

        		<?php if(isset($error) && !empty($error)): ?>
        		<div class="warning"><?php echo $error; ?></div>
        		<?php endif; ?>
        	</form>
        </div>
    </body>
</html>