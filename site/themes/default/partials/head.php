<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

	<?php Flextype\Events::dispatch('onThemeMeta'); ?>

	<link rel="shortcut icon" href="<?php echo Url::getBase(); ?>/favicon.ico">

	<title><?php echo Flextype\Config::get('site.title'); ?> | <?php echo $page['title']; ?></title>

    <link href='https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,600,700' rel='stylesheet' type='text/css'>

    <!-- Bootstrap core CSS -->
	<link href="<?php echo Url::getBase(); ?>/site/themes/<?php echo Flextype\Config::get('site.theme'); ?>/node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
	<link href="<?php echo Url::getBase(); ?>/site/themes/<?php echo Flextype\Config::get('site.theme'); ?>/assets/css/theme.css" rel="stylesheet">
	<?php Flextype\Events::dispatch('onThemeHeader'); ?>
  </head>
  <body>
  <?php Flextype\Templates::display('partials/navigation'); ?>
  <main role="main" class="container content">
