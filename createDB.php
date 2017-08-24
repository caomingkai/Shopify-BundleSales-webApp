<?php
    session_start();
    require_once __DIR__ . '/connectSql.php';

    //-- 1 -- table: originPtoOriginV
    $originPtoOriginV = "OriginPtoOriginV" . $_SESSION["shopId"];
    $result = $conn->query("SHOW TABLES LIKE '".$originPtoOriginV."'");
    if ( $result->num_rows === 0 ) {
      echo "<h1> OriginPtoOriginV table doesn't exists. Now build it..... </h1>";
      $sqlCreateTbl = "CREATE TABLE $originPtoOriginV (
                            originP   VARCHAR(50)  NOT NULL UNIQUE PRIMARY KEY,
                            originV   VARCHAR(500)  NOT NULL
                       )";
      $creatTblRslt = $conn->query($sqlCreateTbl);
      if( !$creatTblRslt ){ echo "<h1> create OriginPtoOriginV table failed! </h1>"; }
      else{ echo "<h1> create OriginPtoOriginV table successfully! </h1>"; }
    }else{
      echo "<h1> OriginPtoOriginV table already exists. </h1>";
    }


    //-- 2 -- table: originVtoShadowV
    $originVtoShadowV = "OriginVtoShadowV" . $_SESSION["shopId"];
    $result = $conn->query("SHOW TABLES LIKE '".$originVtoShadowV."'");
    if ( $result->num_rows === 0 ) {
      echo "<h1> OriginVtoShadowV table doesn't exists. Now build it..... </h1>";
      $sqlCreateTbl = "CREATE TABLE $originVtoShadowV (
                            originV   VARCHAR(50)   NOT NULL  UNIQUE PRIMARY KEY,
                            metaId    VARCHAR(50)   NOT NULL ,
                            shadowV   VARCHAR(500)  NOT NULL
                       )";
      if( !$creatTblRslt = $conn->query($sqlCreateTbl)){
        echo "<h1> create OriginVtoShadowV table failed! </h1>";
      }else{
        echo "<h1> create OriginVtoShadowV table successfully! </h1>";
      }
    }else{
      echo "<h1> OriginVtoShadowV table already exists. </h1>";
    }



    //-- 3 -- table: shadowVToOriginPV
    $shadowVToOriginPV = "ShadowVToOriginPV" . $_SESSION["shopId"];
    $result = $conn->query("SHOW TABLES LIKE '".$shadowVToOriginPV."'");
    if ( $result->num_rows === 0 ) {
      echo "<h1> ShadowVToOriginPV table doesn't exists. Now build it..... </h1>";
      $sqlCreateTbl = "CREATE TABLE $shadowVToOriginPV (
                            shadowV   VARCHAR(50)  NOT NULL  PRIMARY KEY,
                            originP   VARCHAR(50)  NOT NULL,
                            originV   VARCHAR(50)  NOT NULL
                       )";
      if( !$creatTblRslt = $conn->query($sqlCreateTbl)){
        echo "<h1> create ShadowVToOriginPV table failed! </h1>";
      }else{
        echo "<h1> create ShadowVToOriginPV table successfully! </h1>";
      }
    }else{
      echo "<h1> ShadowVToOriginPV table already exists. </h1>";
    }


    //-- 4 --- table: bundleToShadowP
    $bundleToShadowP =  "BundleToShadowP" . $_SESSION["shopId"];
    $result = $conn->query("SHOW TABLES LIKE '".$bundleToShadowP."'");
    if ( $result->num_rows === 0 ) {
      echo "<h1> BundleToShadowP table doesn't exists. Now build it..... </h1>";
      $sqlCreateTbl = "CREATE TABLE $bundleToShadowP (
                            bdl       VARCHAR(50)   NOT NULL  PRIMARY KEY,
                            shadowP   VARCHAR(500)  NOT NULL
                       )";
      if( !$creatTblRslt = $conn->query($sqlCreateTbl)){
        echo "<h1> create BundleToShadowP table failed! </h1>";
      }else{
        echo "<h1> create BundleToShadowP table successfully! </h1>";
      }
    }else{
      echo "<h1> BundleToShadowP table already exists. </h1>";
    }
?>
