<?php
/**
 * NGワードフィルタリング機能
 * 不適切な言葉を検出してブロックします
 */

/**
 * NGワードリストを取得
 * @return array NGワードの配列
 */
function getNgWords() {
    return array(
        // 暴言・侮辱
        'バカ',
        'ばか',
        '馬鹿',
        'アホ',
        'あほ',
        '阿呆',
        'クソ',
        'くそ',
        '糞',
        'ゴミ',
        'ごみ',
        'カス',
        'かす',
        '死ね',
        'しね',
        '殺す',
        'ころす',
        'うざ',
        'ウザ',
        'うざい',
        'ウザい',
        'きもい',
        'キモい',
        'fuck',
        'f*ck',
        'ファック',
        'ふぁっく',
        'ざこ',
        'ザコ',
        '雑魚',
        '土人',


        // 差別的表現
        'ガイジ',
        'がいじ',
        'ゲイ',
        '同性愛者',
        'nigger',
        'ニガー',
        'きちがい',
        'キチガイ',
        '片親',
        '部落',
        'レズ',
        'ホモ',
        'ヤクザ',
        '反社',
        '反社会',
        'yellow monkey',
        'イエローモンキー',


        // スパム関連
        '出会い系',
        'アダルト',
        '援助交際',
        '情報商材',


        // 詐欺関連
        '儲かる',
        '稼げる',
        '簡単に稼ぐ',
        '今すぐ稼げる',
        '最短５分',

        //下ネタ
        'セックス',
        '性行為',
        'せっくす',
        'えろ',
        'えろい',
        'えろ動画',
        'エロ',
        'エロい',
        'エロ動画',
        '成人向け',
        'オナニー',

        //恐喝
        '家燃やす',
        '住所特定',
        '住所',
        '今から行くからな',
        '放火',

        //その他

        //おまけ用
        // I
        'IRR','IRr','IRｒ','IRＲ',
        'IrR','Irr','Irｒ','IrＲ',
        'IｒR','Iｒr','Iｒｒ','IｒＲ',
        'IＲR','IＲr','IＲｒ','IＲＲ',

        // i
        'iRR','iRr','iRｒ','iRＲ',
        'irR','irr','irｒ','irＲ',
        'iｒR','iｒr','iｒｒ','iｒＲ',
        'iＲR','iＲr','iＲｒ','iＲＲ',

        // い
        'いRR','いRr','いRｒ','いRＲ',
        'いrR','いrr','いrｒ','いrＲ',
        'いｒR','いｒr','いｒｒ','いｒＲ',
        'いＲR','いＲr','いＲｒ','いＲＲ',


    );
}

/**
 * テキストにNGワードが含まれているかチェック
 *
 * @param string $text チェックするテキスト
 * @return array|false NGワードが見つかった場合はそのワードの配列、見つからなければfalse
 */
function checkNgWords($text) {
    $ng_words = getNgWords();
    $found_words = array();

    foreach ($ng_words as $word) {
        if (mb_strpos($text, $word) !== false) {
            $found_words[] = $word;
        }
    }

    if (count($found_words) > 0) {
        return $found_words;
    }

    return false;
}

/**
 * 複数のテキストフィールドをまとめてチェック
 *
 * @param array $texts チェックするテキストの連想配列（例：['タイトル' => $title, '内容' => $content]）
 * @return array|false NGワードが見つかった場合は詳細情報の配列、見つからなければfalse
 */
function checkNgWordsMultiple($texts) {
    $results = array();

    foreach ($texts as $field_name => $text) {
        $found = checkNgWords($text);
        if ($found !== false) {
            $results[$field_name] = $found;
        }
    }

    if (count($results) > 0) {
        return $results;
    }

    return false;
}

/**
 * NGワードエラーメッセージを生成
 *
 * @param array $ng_results checkNgWordsMultipleの結果
 * @return string エラーメッセージ
 */
function generateNgWordErrorMessage($ng_results) {
    $message = '不適切な表現が含まれています。';
    return $message;
}
?>
