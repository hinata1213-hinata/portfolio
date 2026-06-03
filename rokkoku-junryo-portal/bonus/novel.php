<?php
session_start();

$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line[0] !== '#' && str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            putenv(trim($k) . '=' . trim($v));
        }
    }
}
$correct_password = getenv('NOVEL_PASSWORD');
$error_message = '';
$is_authenticated = false;

// セッションで認証済みかチェック
if (isset($_SESSION['novel_authenticated']) && $_SESSION['novel_authenticated'] === true) {
    $is_authenticated = true;
}

// パスワード送信時の処理
if (isset($_POST['password'])) {
    if ($_POST['password'] === $correct_password) {
        $_SESSION['novel_authenticated'] = true;
        $is_authenticated = true;
    } else {
        $error_message = '合言葉が違います';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>小説版『六刻巡旅』</title>
    <link rel="stylesheet" href="../game.css">
    <style>
        body {
            background: linear-gradient(180deg, #000000, #0a0a0a);
            min-height: 100vh;
            padding-top: 80px;
            padding-bottom: 60px;
        }

        .site-header {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0, 102, 204, 0.3);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            font-family: 'Creepster', cursive;
            font-size: 24px;
            color: #0066CC;
            text-decoration: none;
            text-shadow: 0 0 10px #0066CC;
        }

        .back-button {
            padding: 10px 25px;
            background: rgba(0, 102, 204, 0.2);
            border: 1px solid rgba(0, 102, 204, 0.5);
            border-radius: 25px;
            color: #0066CC;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: rgba(0, 102, 204, 0.3);
            box-shadow: 0 0 15px rgba(0, 102, 204, 0.5);
        }

        /* パスワード入力画面 */
        .password-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 0 20px;
            text-align: center;
        }

        .password-box {
            background: rgba(26, 26, 26, 0.9);
            border: 2px solid rgba(0, 102, 204, 0.4);
            border-radius: 15px;
            padding: 50px 40px;
        }

        .password-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .password-title {
            color: #0066CC;
            font-size: 1.8rem;
            margin-bottom: 15px;
            text-shadow: 0 0 10px rgba(0, 102, 204, 0.5);
        }

        .password-description {
            color: #888888;
            font-size: 1rem;
            margin-bottom: 20px;
            line-height: 1.8;
        }

        .password-hint {
            color: #6699CC;
            font-size: 0.9rem;
            margin-bottom: 30px;
            line-height: 2;
            padding: 15px 20px;
            background: rgba(0, 102, 204, 0.1);
            border-left: 3px solid #0066CC;
            border-radius: 0 5px 5px 0;
        }

        .password-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .password-input {
            padding: 15px 20px;
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid rgba(0, 102, 204, 0.3);
            border-radius: 10px;
            color: #ffffff;
            font-size: 1.2rem;
            text-align: center;
            letter-spacing: 5px;
            font-family: monospace;
        }

        .password-input:focus {
            outline: none;
            border-color: #0066CC;
            box-shadow: 0 0 15px rgba(0, 102, 204, 0.3);
        }

        .password-input::placeholder {
            letter-spacing: normal;
            color: #666666;
        }

        .password-submit {
            padding: 15px 40px;
            background: linear-gradient(135deg, #0066CC, #0080FF);
            border: none;
            border-radius: 25px;
            color: white;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .password-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 102, 204, 0.5);
        }

        .error-message {
            color: #ff4444;
            font-size: 0.95rem;
            margin-top: 10px;
        }

        /* 小説表示画面 */
        .novel-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .novel-header {
            text-align: center;
            margin-bottom: 60px;
            padding-bottom: 40px;
            border-bottom: 1px solid rgba(0, 102, 204, 0.3);
        }

        .novel-title {
            color: #0066CC;
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 0 0 20px rgba(0, 102, 204, 0.5);
        }

        .chapter {
            margin-bottom: 60px;
        }

        .chapter-title {
            color: #0066CC;
            font-size: 1.5rem;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0, 102, 204, 0.2);
        }

        .novel-text {
            color: #e0e0e0;
            font-size: 1.1rem;
            line-height: 2.2;
            text-align: justify;
        }

        .novel-text p {
            margin-bottom: 1.5em;
            text-indent: 1em;
        }

        .novel-text .dialogue {
            text-indent: 0;
        }

        .novel-text .emphasis {
            color: #0066CC;
        }

        .novel-text .thought {
            color: #aaaaaa;
            font-style: italic;
        }

        .novel-end {
            text-align: center;
            margin-top: 80px;
            padding-top: 40px;
            border-top: 1px solid rgba(0, 102, 204, 0.3);
        }

        .end-text {
            color: #0066CC;
            font-size: 1.3rem;
            font-weight: bold;
            letter-spacing: 5px;
        }

        .end-subtitle {
            color: #888888;
            font-size: 0.9rem;
            margin-top: 15px;
        }

        @media (max-width: 768px) {
            .novel-title {
                font-size: 1.8rem;
            }

            .novel-text {
                font-size: 1rem;
                line-height: 2;
            }

            .password-box {
                padding: 40px 25px;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <nav class="navbar">
            <a href="../game.html" class="logo">六刻巡旅</a>
            <a href="../game.html" class="back-button">← 戻る</a>
        </nav>
    </header>

    <?php if (!$is_authenticated): ?>
    <!-- パスワード入力画面 -->
    <div class="password-container">
        <div class="password-box">
            <div class="password-icon">🔒</div>
            <h1 class="password-title">合言葉を入力せよ</h1>
            <p class="password-description">
                この先は、旅を終えた者のみが<br>
                読むことを許される物語。
            </p>
            <p class="password-hint">
                １文字目の合言葉はEND1クリア後<br>
                ２文字目の合言葉はEND2クリア後<br>
                ３文字目の合言葉はEND5クリア後<br>
            </p>
            <form method="post" class="password-form">
                <input type="text" name="password" class="password-input" placeholder="合言葉" autocomplete="off" required>
                <button type="submit" class="password-submit">解錠する</button>
            </form>
            <?php if (!empty($error_message)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <!-- 小説表示画面 -->
    <div class="novel-container">
        <div class="novel-header">
            <h1 class="novel-title">小説版『六刻巡旅』</h1>
        </div>

        <div class="chapter">
            <h2 class="chapter-title">序章</h2>
            <div class="novel-text">
                <p>山道を走るバスに揺られていた。</p>
                <p>窓の外には、鬱蒼とした杉林が続いている。どこまでも同じ景色。なぜ俺はこんな場所に向かっているのか。</p>
                <p>三ヶ月前に母が亡くなり、その後を追うように祖母も逝った。母方の祖母だ。六刻村という山奥の集落に住んでいたらしい。会ったことはない。母が連絡を絶っていたからだ。</p>
                <p>祖母の法要に出てほしいと、従姉の雪音さんから連絡があった。</p>
                <p>正直、迷った。会ったこともない祖母のために、わざわざ山奥まで行く気にはなれなかった。</p>
                <p>でも、母の遺品を整理していたとき、一通の手紙を見つけた。</p>
                <p class="dialogue">『あの子に、ごめんなさいと伝えて』</p>
                <p>それだけが書かれていた。誰のことか、分からない。母は生前、故郷の話を一切しなかった。何か隠しているようだった。</p>
                <p>この村に行けば、その理由が分かるかもしれない。</p>
                <p>バスは山道を登り続ける。対向車は一台もない。携帯の電波も、いつの間にか消えていた。</p>
                <p>やがて、バスが止まった。</p>
                <p>降りると、8月なのに空気が冷たい。</p>
                <p class="thought">——この景色を、どこかで見た気がする。</p>
                <p>錆びた鳥居。苔むした石段。蝉の声すら聞こえない。</p>
                <p class="dialogue">「——薫くん」</p>
                <p>振り返ると、白いワンピースの少女が立っていた。白すぎる肌。足元に——<span class="emphasis">影がない</span>。</p>
                <p class="dialogue">「覚えてる？ 私、灯凛だよ」</p>
                <p>その名前に、胸の奥が疼いた。知っている——気がする。</p>
                <p class="dialogue">「……灯凛」</p>
                <p>名前を口にした瞬間、記憶が蘇った。</p>
                <p class="thought">——幼い頃の夏。神社の境内。蝉の声。泣いている女の子がいた。俺は言った。</p>
                <p class="dialogue">『大きくなったら、お嫁さんにしてあげる』</p>
                <p>あの子が——灯凛だった。</p>
                <p>彼女の目に涙が光った。</p>
                <p class="dialogue">「覚えてて、くれたんだ」</p>
                <p>冷たい腕が俺の首に回る。体の芯まで冷えていく。それでも——離れられなかった。</p>
                <p class="dialogue">「ずっと待ってた」</p>
                <p class="dialogue">「薫くん！」</p>
                <p>声がした方を見ると、従姉の雪音さんが息を切らせて走ってきた。まるで——何かを察したかのように。</p>
                <p>彼女は俺と灯凛の間に割り込み、俺の腕を掴んだ。</p>
                <p class="dialogue">「行くわよ！」</p>
                <p>雪音さんに引っ張られるまま、走った。砂利を蹴る音。自分の息遣い。</p>
                <p>振り返ると、灯凛はまだ立っていた。影のない足元。</p>
                <p class="dialogue">「また会おうね」</p>
                <p>その声は——耳元で囁かれたように近かった。</p>
            </div>
        </div>

        <div class="chapter">
            <h2 class="chapter-title">一日目・夜</h2>
            <div class="novel-text">
                <p>祖母の家。</p>
                <p>雪音さんが言った。</p>
                <p class="dialogue">「灯凛ちゃんは六年前に死んでいるの」</p>
                <p class="dialogue">「……何を」</p>
                <p class="dialogue">「『刻守』として神域に入った。二度と戻らない、神様の花嫁として」</p>
                <p>俺は黙った。</p>
                <p class="dialogue">「この村には『還り人』という現象がある。未練を抱えた死者が戻ってくる」</p>
                <p>影のない足元。8月なのに凍えるような冷たさ。——あれが、死者だというのか。</p>
                <p class="dialogue">「深入りしないで。——引き込まれるわ」</p>
                <p class="dialogue">「どうしてあの時、俺の所に来たんですか」</p>
                <p class="dialogue">「……還り人が現れると、村全体が冷えるの。嫌な予感がして、バス停に向かったら——」</p>
                <p>雪音さんは言葉を切った。</p>
                <p class="dialogue">「あなたが、灯凛ちゃんと一緒にいた」</p>
                <p>その夜。眠れずに縁側に出た。</p>
                <p>月明かりの中、庭に人影があった。白いワンピースがぼんやりと光っている。</p>
                <p class="thought">——灯凛だろうか。</p>
                <p class="dialogue">「……また会えたね」</p>
                <p>彼女はゆっくりと近づいてきた。</p>
                <p class="dialogue">「怖い？ 私のこと」</p>
                <p>灯凛が首を傾げる。</p>
                <p class="dialogue">「……分からない」</p>
                <p>俺は正直に答えた。</p>
                <p>彼女が隣に座った。冷気が伝わる。</p>
                <p class="dialogue">「私ね、死ぬのは怖くなかった。でも——薫くんに会えないまま消えるのが怖かった」</p>
                <p class="dialogue">「……」</p>
                <p class="dialogue">「ねえ、薫くん。何しにここへ来たの？」</p>
                <p>彼女の目が、俺を真っ直ぐ見つめる。</p>
                <p class="dialogue">「——私を迎えに来てくれたんでしょう？」</p>
                <p>俺は少し考えてから、答えた。</p>
                <p class="dialogue">「母さんの遺言で来た。『あの子にごめんなさい』と」</p>
                <p>灯凛は目を見開いた。</p>
                <p class="dialogue">「……叔母さんが」</p>
                <p class="dialogue">「何があった？」</p>
                <p class="dialogue">「叔母さんは私が刻守に選ばれた時、反対してくれた。でも止められなかった」</p>
                <p>灯凛は微笑んだ。少しだけ温かく。</p>
                <p class="dialogue">「怒ってないよ。伝えてくれて、ありがとう」</p>
            </div>
        </div>

        <div class="chapter">
            <h2 class="chapter-title">二日目</h2>
            <div class="novel-text">
                <p>神社で、神主の老人と会った。</p>
                <p class="dialogue">「灯凛の未練はお前だ」</p>
                <p>老人は言った。</p>
                <p class="dialogue">「あの子を救う方法は二つ。お前が拒絶するか——代わりに神域へ入るか」</p>
                <p>老人はそれだけ言うと、奥へ消えた。</p>
                <p>拒絶か、身代わりか。</p>
                <p>どちらも——選べるはずがない。</p>
                <p>重い足取りで石段を降りる。蝉の声がない夏の空気が、肌に纏わりつく。</p>
                <p>神社を出ると、雪音さんが待っていた。</p>
                <p class="dialogue">「聞いてたわ」</p>
                <p class="dialogue">「ああ」</p>
                <p class="dialogue">「どちらも選ばないで」</p>
                <p>彼女は俺の手を握った。</p>
                <p class="dialogue">「灯凛ちゃんは私の親友だった。六年前、叔母さんと一緒に村人に抗議した。でも止められなかった。——私も逃げたの」</p>
                <p class="dialogue">「雪音さん……」</p>
                <p class="dialogue">「だから、あなたまで失いたくない」</p>
                <p>俺は答えた。</p>
                <p class="dialogue">「拒絶でも身代わりでもない方法を探す」</p>
                <p>雪音さんは小さく笑った。</p>
                <p class="dialogue">「……神社の蔵に古い文献がある。一緒に調べましょう」</p>
                <p>古い文献を調べたが、還り人を救う確実な方法は見つからなかった。</p>
                <p>ただ一つ、記されていたのは——</p>
                <p class="thought">——還り人の未練が晴れぬ限り、魂は彷徨い続ける。</p>
                <p class="dialogue">「……未練を晴らす方法は、本人にしか分からないのかもしれない」</p>
                <p>雪音さんが呟いた。</p>
            </div>
        </div>

        <div class="chapter">
            <h2 class="chapter-title">二日目・夜</h2>
            <div class="novel-text">
                <p>その夜、眠れなかった。</p>
                <p>灯凛のことが頭から離れない。</p>
                <p>気づけば、家を出ていた。</p>
                <p>月明かりの下、川沿いを歩く。なぜここに来たのか、自分でも分からない。</p>
                <p class="thought">——いや、分かっている。</p>
                <p>彼女に、会いたかったのだ。</p>
                <p class="dialogue">「……来てくれたんだ」</p>
                <p>振り返ると、灯凛が立っていた。</p>
                <p>水面が彼女の足元から凍っていく。</p>
                <p class="dialogue">「私と一緒に来て」</p>
                <p>彼女が手を差し伸べた。</p>
                <p class="dialogue">「神域で、永遠に一緒にいよう」</p>
                <p>俺は彼女の目を見つめた。</p>
                <p class="dialogue">「行けない。でも……見送りたい」</p>
                <p>灯凛は目を伏せた。</p>
                <p class="dialogue">「……そっか」</p>
                <p>彼女の声は、どこか諦めたように響いた。</p>
                <p class="dialogue">「薫くんは、生きてる人だもんね」</p>
                <p class="dialogue">「灯凛……」</p>
                <p class="dialogue">「怒ってないよ。来てくれただけで、嬉しかった」</p>
                <p>彼女が微笑む。泣き笑いのような顔だった。</p>
                <p class="dialogue">「私はまだ、ここにいるから」</p>
                <p class="dialogue">「……また、会いに来る」</p>
                <p class="dialogue">「うん。……約束だよ」</p>
                <p>灯凛の姿が薄れていく。</p>
                <p>消えたわけじゃない。ただ、夜の闘に溶けていくように。</p>
                <p>俺は一人、川沿いに立ち尽くしていた。</p>
            </div>
        </div>

        <div class="chapter">
            <h2 class="chapter-title">終章</h2>
            <div class="novel-text">
                <p>村を離れる日が来た。</p>
                <p>バス停で、雪音さんが見送ってくれた。</p>
                <p class="dialogue">「また来るの？」</p>
                <p class="dialogue">「ああ。約束したから」</p>
                <p>雪音さんは微笑んだ。</p>
                <p class="dialogue">「灯凛ちゃん、待ってるわね」</p>
                <p>バスが来た。俺は乗り込み、窓の外を見た。</p>
                <p>錆びた鳥居。苔むした石段。</p>
                <p>その奥に、白いワンピースの影が見えた気がした。</p>
                <p class="thought">——また、会いに来る。</p>
                <p>俺は心の中で呟いた。</p>
                <p>バスが動き出す。村が遠ざかっていく。</p>
                <p>でも、繋がりは消えない。</p>
                <p><span class="emphasis">愛とは、手放すこと。</span></p>
                <p><span class="emphasis">そして——また会いに行くこと。</span></p>
            </div>
        </div>

        <div class="novel-end">
            <p class="end-text">—END—</p>
            <p class="end-subtitle">Thank you for reading.</p>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>