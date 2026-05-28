<section class="search-section">
    <h2>投稿検索</h2>
    <div class="search-box">
        <input id="title-search" type="text" placeholder="タイトルの一部を入力（例：タイトル）" autocomplete="off">
    </div>
    <div id="search-results" class="search-results">
        <!-- 検索結果がここに差し替え表示されます -->
    </div>
</section>

<script>
(function(){
    const input = document.getElementById('title-search');
    const results = document.getElementById('search-results');
    let timer = null;

    function escapeHtml(s){
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function render(items){
        if(!items || items.length === 0){
            results.innerHTML = '<p class="no-results">該当する投稿はありません。</p>';
            return;
        }
        const html = items.map(item => {
            const title = escapeHtml(item.title || '');
            const name = escapeHtml(item.name || '匿名');
            const content = escapeHtml(item.content || '').replace(/\n/g, '<br>');
            const img = item.image ? ('<div class="result-image"><img src="./images/' + encodeURI(item.image) + '" alt="" style="max-width:200px;border-radius:6px;"></div>') : '';
            // もし投稿詳細ページがあるならリンク先を調整してください（例: index_post_detail.php?id=...）
            const link = './index_post_detail.php?id=' + encodeURIComponent(item.id);
            return '<article class="result-item">' +
                        '<h3><a href="' + link + '">' + title + '</a></h3>' +
                        '<div class="meta">投稿者: ' + name + '</div>' +
                        '<div class="excerpt">' + content + '</div>' +
                        img +
                   '</article>';
        }).join('');
        results.innerHTML = html;
    }

    function doSearch(q){
        const url = 'index_search_done.php?q=' + encodeURIComponent(q);
        fetch(url, {cache: 'no-store'})
            .then(resp => {
                if(!resp.ok) throw new Error('ネットワークエラー');
                return resp.json();
            })
            .then(data => {
                if(data.error){
                    results.innerHTML = '<p class="error">検索に失敗しました</p>';
                    console.error(data.error);
                    return;
                }
                render(data);
            })
            .catch(err => {
                results.innerHTML = '<p class="error">検索に失敗しました</p>';
                console.error(err);
            });
    }

    // 初期表示：空なら最新を取得する
    doSearch('');

    input.addEventListener('input', function(e){
        clearTimeout(timer);
        const q = e.target.value.trim();
        timer = setTimeout(() => {
            doSearch(q);
        }, 250); // デバウンス：250ms
    });
})();
</script>