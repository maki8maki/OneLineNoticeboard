<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>1行掲示板</title>
</head>
<body>
    <span style="font-size:25px;">「あなたが言ってみたい場所は？」<br></span>
    <?php
        // データベースへの接続
        $dsn = 'mysql:dbname={database name};host=localhost';
        $user = '{user name}';
        $password = '{password}';
        $pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

        // テーブルの作成
        $table_name = "tb_posting_system";
        $sql = "create table if not exists ".$table_name
            ." ("
            . "id INT AUTO_INCREMENT PRIMARY KEY,"
            . "name char(32),"
            ."comment TEXT,"
            ."date datetime,"
            ."password TEXT"
            .");";
        $stmt = $pdo->query($sql);

        // 二重送信に対応
        session_start();
        // 編集に利用
        $edit_name = "";
        $edit_comment = "";
        $edit_password = "";
        $posted_num_edit = "";

        // 出力
        $red = "color:red;";
        $big = "font-size:20px;";

        if (isset($_REQUEST["POST_TOKEN"]) && $_REQUEST["POST_TOKEN"] === $_SESSION["POST_TOKEN"]) { // 二重送信対策
            if (isset($_POST['submit'])) { // 送信フォームの場合
                $date = date("Y/m/d H:i:s"); // 時間の取得
                 // 全て書かれていればテーブルに保存
                if(!empty($_POST["name"]) && trim($_POST["name"]) != "" &&
                    !empty($_POST["comment"]) && trim($_POST["comment"]) != "" &&
                        !empty($_POST["password"]) && trim($_POST["password"]) != "") {
                    $name = trim($_POST["name"]); // 名前の取得（前後の空白は削除，以下同様）
                    $comment = trim($_POST["comment"]); // コメントの取得
                    $password = trim($_POST["password"]); // パスワードの取得
                    if (empty($_POST["post_num_edit"])) { // 編集番号が空なら新規送信
                        $sql = $pdo -> prepare("insert into ".$table_name." (name, comment, date, password) values (:name, :comment, :date, :password)");
                        $sql -> bindParam(':name', $name, PDO::PARAM_STR);
                        $sql -> bindParam(':comment', $comment, PDO::PARAM_STR);
                        $sql -> bindParam(':date', $date, PDO::PARAM_STR);
                        $sql -> bindParam(':password', $password, PDO::PARAM_STR);
                    } else { // 番号があれば編集送信
                        $edit_num = $_POST["post_num_edit"]; // 編集対象番号の取得
                        $sql = $pdo -> prepare("update ".$table_name." set name=:name, comment=:comment where id=:id");
                        $sql -> bindParam(':name', $name, PDO::PARAM_STR);
                        $sql -> bindParam(':comment', $comment, PDO::PARAM_STR);
                        $sql -> bindParam(':id', $edit_num, PDO::PARAM_INT);
                    }
                    $sql -> execute();
                } else {
                    if (empty($_POST["post_num_edit"])) {
                        echo "<span style=".$big.">[New Post]<br></span>";
                    } else {
                        echo "<span style=".$big.">[Editorial Post]<br></span>";
                    }
                }
                if (empty($_POST["name"])) {
                    echo "<span style=".$red.">Input Name!!<br></span>";
                }
                if (empty($_POST["comment"])) {
                    echo "<span style=".$red.">Input Comment!!<br></span>";
                }
                if (empty($_POST["password"])) {
                    echo "<span style=".$red.">Input Password!!<br></span>";
                }
            } elseif (isset($_POST['delete'])) { // 削除フォームの場合
                if(!empty($_POST["del_num"]) && !empty($_POST["password"])){
                    $del_num = $_POST["del_num"]; // 削除対象番号の取得
                    $password = $_POST["password"]; // パスワードの取得
                    $sql = $pdo -> prepare("delete from ".$table_name." where id=:id and password=:password limit 1");
                    $sql -> bindParam(':id', $del_num, PDO::PARAM_INT);
                    $sql -> bindParam(':password', $password, PDO::PARAM_STR);
                    $sql -> execute();
                    if ($sql -> rowCount() == 0) {
                        echo "<span style=".$big.">[Deletion]<br></span>";
                        echo "<span style=".$red.">Invalid Number or Wrong Password!!<br></span>";
                    }
                } else {
                    echo "<span style=".$big.">[Deletion]<br></span>";
                }
                if (empty($_POST["del_num"])) {
                    echo "<span style=".$red.">Input Number!!<br></span>";
                }
                if (empty($_POST["password"])) {
                    echo "<span style=".$red.">Input Password!!<br></span>";
                }
            } elseif (isset($_POST['edit'])) { // 編集フォームの場合
                if (!empty($_POST['edit_num']) && !empty($_POST["password"])) {
                    $edit_num = $_POST["edit_num"]; // 編集対象番号の取得
                    $password = $_POST["password"]; // パスワードの取得
                    $sql = $pdo -> prepare("select * from ".$table_name." where id=:id and password=:password");
                    $sql -> bindParam(':id', $edit_num, PDO::PARAM_INT);
                    $sql -> bindParam(':password', $password, PDO::PARAM_STR);
                    $sql -> execute();
                    if ($results = $sql -> fetchAll()) {
                        $posted_num_edit = $edit_num;
                        $edit_name = $results[0]["name"];
                        $edit_comment = $results[0]["comment"];
                        $edit_password = $password;
                    } else {
                        echo "<span style=".$big.">[Editing]<br></span>";
                        echo "<span style=".$red.">Invalid Number or Wrong Password!!<br></span>";
                    }
                } else {
                    echo "<span style=".$big.">[Editing]<br></span>";
                }
                if (empty($_POST["edit_num"])) {
                    echo "<span style=".$red.">Input Number!!<br></span>";
                }
                if (empty($_POST["password"])) {
                    echo "<span style=".$red.">Input Password!!<br></span>";
                }
            }
        }
        $_SESSION["POST_TOKEN"] = uniqid();
    ?>
    <form action="" method="post">
        <input type="text" name="name" placeholder="名前" value=<?= $edit_name ?>>
        <input type="text" name="comment" placeholder="コメント" value=<?= $edit_comment ?>>
        <input type="password" name="password" placeholder="パスワード" value=<?= $edit_password ?> <?php if(!empty($edit_password)){echo "readonly";} ?>>
        <input type="hidden" name="post_num_edit" value=<?= $posted_num_edit ?>>
        <input type="hidden" name="POST_TOKEN" value="<?php echo $_SESSION["POST_TOKEN"]; ?>"/>
        <input type="submit" name="submit" value="投稿">
    </form>
    <form action="" method="post">
        <input type="text" name="del_num" placeholder="削除番号">
        <input type="password" name="password" placeholder="パスワード">
        <input type="hidden" name="POST_TOKEN" value="<?php echo $_SESSION["POST_TOKEN"]; ?>"/>
        <input type="submit" name="delete" value="削除">
    </form>
    <form action="" method="post">
        <input type="text" name="edit_num" placeholder="編集番号">
        <input type="password" name="password" placeholder="パスワード">
        <input type="hidden" name="POST_TOKEN" value="<?php echo $_SESSION["POST_TOKEN"]; ?>"/>
        <input type="submit" name="edit" value="編集">
    </form>
    <?php
        $display_keys = ["id", "name", "comment", "date"];
        $sql = $pdo -> query("select * from ".$table_name);
        $results = $sql -> fetchAll();
        foreach($results as $row) {
            foreach($display_keys as $key) {
                if ($key == "date") {
                    echo str_replace("-", "/", $row[$key])." ";
                } else {
                    echo $row[$key]." ";
                }
            }
            echo "<br>";
        }
    ?>
</body>
</html>