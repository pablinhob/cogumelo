<?php

class CacheUtilsController {


  //
  //  LESS METHODS
  //

  public static function generateAllLessCaches() {

    $mediaserverControl = new MediaserverController();

    // tmp less dir
    $tmpLessDir = self::prepareLessTmpdir();

    // list, compile and cache files
    $tmpLessFiles = self::listFolderFiles( $tmpLessDir , array('less'),  false);

    if( count($tmpLessFiles) > 0 ){
      foreach( $tmpLessFiles as $lessFilePath ) {

        $path = str_replace( $tmpLessDir, '', $lessFilePath );

        if( mb_substr( $path , 0, 7 ) === 'classes' ) {
          $moduleName = false;
          $relativeFilePath = str_replace('classes/view/templates/', '', $path);
        }
        else {
          $expPath = explode('/', $path);
          $moduleName = $expPath[0];

          $relativeFilePath = str_replace($moduleName.'/classes/view/templates/', '', $path);
        }


        if(
           preg_match('#\/master(.*).less#', $relativeFilePath) > 0 ||
           preg_match('#\/primary(.*).less#', $relativeFilePath) > 0
         ){
          $mediaserverControl->compileAndCacheLess( $relativeFilePath, $moduleName );
          //echo "\n\n-----".$relativeFilePath;
        }


      }
    }

    // remove tmp less dir
    self::removeLessTmpdir();
  }

  // crea estructura con todos os arquivos LESS para a súa futura compilación
  public static function prepareLessTmpdir() {
    global $CACHE_UTILS_LESS_TMPDIR;
    global $C_ENABLED_MODULES;

    if( $CACHE_UTILS_LESS_TMPDIR ) {
      $destino = $CACHE_UTILS_LESS_TMPDIR;
    }
    else {
      $destino = Cogumelo::getSetupValue( 'mod:mediaserver:tmpCachePath' ).'/lesstmp/'.self::generateLessTmpdirName().'/';

      mkdir( $destino, 0750, true );

      $cacheableFolder = 'classes/view/templates/';

      foreach( $C_ENABLED_MODULES as $moduleName ){

        // cogumelo modules
        self::copyLessTmpdir(
          COGUMELO_LOCATION.'/coreModules/',
          $moduleName.'/'.$cacheableFolder,
          $destino
        );

        // DIST modules
        if( defined('COGUMELO_DIST_LOCATION') && COGUMELO_DIST_LOCATION !== false ) {
          self::copyLessTmpdir(
            COGUMELO_DIST_LOCATION.'/distModules/',
            $moduleName.'/'.$cacheableFolder,
            $destino
          );
        }

        // app modules
        self::copyLessTmpdir(
          APP_BASE_PATH.'/modules/',
          $moduleName.'/'.$cacheableFolder,
          $destino
        );

      }

      // app files
      self::copyLessTmpdir(
        APP_BASE_PATH.'/',
        $cacheableFolder,
        $destino
      );

      $CACHE_UTILS_LESS_TMPDIR = $destino;
    }

    return $destino;
  }


  public static function copyLessTmpdir( $origDir, $filePath, $destDir ) {

    $includeFiles = array('less');
    $fileList = self::listFolderFiles( $origDir.$filePath , $includeFiles, false );

    foreach ( $fileList as $filePath ) {
      $relativeFilePath = mb_substr( $filePath, mb_strlen($origDir) );
      if( !file_exists( dirname($destDir.$relativeFilePath) ) ) {
        mkdir( dirname($destDir.$relativeFilePath ), 0750, true );
      }
      copy( $filePath, $destDir.$relativeFilePath );
    }
  }

  public static function removeLessTmpdir( $dir = false ) {
    global $CACHE_UTILS_LESS_TMPDIR;

    if( $CACHE_UTILS_LESS_TMPDIR ) {
      if( !$dir ) {
        $dir = $CACHE_UTILS_LESS_TMPDIR;
      }
      $files = array_diff(scandir($dir), array('.','..'));
      foreach( $files as $file ) {
        (is_dir("$dir/$file")) ? self::removeLessTmpdir("$dir/$file") : unlink("$dir/$file");
      }
      rmdir($dir);
    }
  }


  public static function generateLessTmpdirName() {
    $length = 5;
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $randomString = '';
    for( $i = 0; $i < $length; $i++ ) {
      $randomString .= $characters[rand(0, mb_strlen($characters) - 1)];
    }

    if( file_exists( $randomString ) ){
      $randomString = self::generateLessTmpdirName();
    }

    return $randomString;
  }


  //
  //  END LESS METHODS
  //



  public static function generateAllCaches() {

    global $C_ENABLED_MODULES;
    $cacheableFolder = 'classes/view/templates/';

    foreach( $C_ENABLED_MODULES as $moduleName ){

      // cogumelo modules
      self::cacheFolder(
        COGUMELO_LOCATION.'/coreModules/'.$moduleName.'/'.$cacheableFolder,
        $moduleName
      );

      // DIST modules
      if( defined('COGUMELO_DIST_LOCATION') && COGUMELO_DIST_LOCATION !== false ) {
        self::cacheFolder(
          COGUMELO_DIST_LOCATION.'/distModules/'.$moduleName.'/'.$cacheableFolder,
          $moduleName
        );
      }

      // app modules
      self::cacheFolder(
        APP_BASE_PATH.'/modules/'.$moduleName.'/'.$cacheableFolder,
        $moduleName
      );
    }

    // app files
    self::cacheFolder( APP_BASE_PATH.'/'.$cacheableFolder );

    // all less files
    self::generateAllLessCaches();
  }

  public static function cacheFolder( $folder, $moduleName = false ) {
    $mediaserverControl = new MediaserverController();

    $fileList = self::listFolderFiles( $folder , array('php', 'tpl', 'less'), true );

    if( count( $fileList ) > 0 ) {
      foreach ( $fileList as $filePath ) {
        $mediaserverControl->cacheContent( str_replace($folder, '' , $filePath ), $moduleName, true );
      }
    }
  }

  // recursive list folder
  public static function listFolderFiles( $folder, $extensions, $excludeExtensions ) {
    $paths = array();

    if( is_dir( $folder ) ) {
      $iter = new RecursiveIteratorIterator(
          new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
          RecursiveIteratorIterator::SELF_FIRST,
          RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
      );

      foreach( $iter as $path ) {
        if( is_file($path) ) {
          if( $excludeExtensions ) {
            if( self::excludeExtensions($path, $extensions ) ) {
              $paths[] = $path;
            }
          }
          else {
            if( self::includeExtensions($path, $extensions ) ) {
              $paths[] = $path;
            }
          }
        }
      }

    }

    return $paths;
  }


  // exclude path that have an extensión of array
  public static function excludeExtensions( $filePath, $extArray ) {
    $ret = true;
    $found = false;

    foreach ( $extArray as $ext ) {
      if( mb_substr( $filePath, -(mb_strlen($ext)+1) ) == '.'.$ext ) {
        $found = true;
      }
    }

    if( $found == true ){
      $ret = false;
    }

    return $ret;
  }

  // include path that have an extensión of array
  public static function includeExtensions( $filePath, $extArray ) {
    $ret = false;

    foreach ( $extArray as $ext ) {
      if( mb_substr( $filePath, -(mb_strlen($ext)+1) ) == '.'.$ext ) {
        $ret = true;
      }
    }

    return $ret;
  }


}
