const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');
const enemyBullets = [];
const bgm = document.getElementById('bgm');
const gameOverSound = document.getElementById('gameOverSound');
const shootSound = document.getElementById('shootSound');
const jumpSound1 = document.getElementById('jumpSound1');
const jumpSound2 = document.getElementById('jumpSound2');
const hariSound = document.getElementById('hariSound');
const stars = [];
for (let i = 0; i < 50; i++) {
    stars.push({
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height,
        size: Math.random() * 2 + 1,
        speed: Math.random() * 2 + 1
    });
}

gameOverSound.volume = 1.0; // 最大音量に設定
gameClearSound.volume = 1.0; // 最大音量に設定
jumpSound1.volume = 1.0; // 最大音量に設定
jumpSound2.volume = 1.0; // 最大音量に設定
hariSound.volume = 1.0; // 最大音量に設定

const player = {
    x: canvas.width / 2 - 25,
    y: canvas.height - 60,
    width: 50,
    height: 50,
    speed: 2.8, // 移動速度を遅くする
    bullets: [],
    isJumping: false,
    jumpSpeed: 0,
    moveLeft: false,
    moveRight: false,
    jumpCount: 0,
    facingRight: true
};



const enemy = {
    x: canvas.width - 75,
    y: canvas.height / 2 - 5 - 50, // 足場の座標
    width: 50,
    height: 50,
    speed: 2,
    moveLeft: false,
    moveRight: false,
    life: 100,//相手のライフポイント
    maxLife: 100,
    facingRight: true
};

const platform = {//敵が乗っている足場
    x: canvas.width - 100,
    y: canvas.height / 2,
    width: 100,
    height: 10
};

const waterZone = {//水中の玉
    x: canvas.width / 2 - 95,
    y: canvas.height / 2 - 95,
    radius: 95
};

const bouncingBall = {//反射する玉１
    x: enemy.x, // 敵の近くに初期位置を設定
    y: enemy.y,
    radius: 5, // 小さくする
    speedX: 3,
    speedY: 3,
    active: false // 初期状態では非アクティブ
};

const bouncingBall2 = {//反射する玉２
    x: enemy.x, // 敵の近くに初期位置を設定
    y: enemy.y,
    radius: 5, // 小さくする
    speedX: 3,
    speedY: 3,
    active: false // 初期状態では非アクティブ
};

const bouncingBall3 = { // 反射する玉３
    x: enemy.x, // 敵の近くに初期位置を設定
    y: enemy.y,
    radius: 5, // 小さくする
    speedX: 3,
    speedY: 3,
    active: false // 初期状態では非アクティブ
};


const spikes = {//針
    x: 0,
    y: canvas.height - 10, // 地上に配置
    width: canvas.width,
    height: 10,
    active: false // 初期状態では非アクティブ
};



let canShoot = true;
let gameStarted = false;
let gameOver = false;
let gameClear = false;

function drawPlayer() {//プレイヤー
    ctx.fillStyle = 'white';
    if (player.facingRight) {
        // 右向きの人型
        ctx.beginPath();
        ctx.arc(player.x + 25, player.y + 15, 10, 0, Math.PI * 2, true); // 頭
        ctx.moveTo(player.x + 25, player.y + 25);
        ctx.lineTo(player.x + 25, player.y + 40); // 体
        ctx.moveTo(player.x + 25, player.y + 30);
        ctx.lineTo(player.x + 35, player.y + 35); // 右腕
        ctx.moveTo(player.x + 25, player.y + 30);
        ctx.lineTo(player.x + 15, player.y + 35); // 左腕
        ctx.moveTo(player.x + 25, player.y + 40);
        ctx.lineTo(player.x + 35, player.y + 50); // 右脚
        ctx.moveTo(player.x + 25, player.y + 40);
        ctx.lineTo(player.x + 15, player.y + 50); // 左脚
        ctx.stroke();

        // 銃
        ctx.fillStyle = 'gray';
        ctx.fillRect(player.x + 35, player.y + 30, 10, 5);
    } else {
        // 左向きの人型
        ctx.beginPath();
        ctx.arc(player.x + 25, player.y + 15, 10, 0, Math.PI * 2, true); // 頭
        ctx.moveTo(player.x + 25, player.y + 25);
        ctx.lineTo(player.x + 25, player.y + 40); // 体
        ctx.moveTo(player.x + 25, player.y + 30);
        ctx.lineTo(player.x + 15, player.y + 35); // 右腕
        ctx.moveTo(player.x + 25, player.y + 30);
        ctx.lineTo(player.x + 35, player.y + 35); // 左腕
        ctx.moveTo(player.x + 25, player.y + 40);
        ctx.lineTo(player.x + 15, player.y + 50); // 右脚
        ctx.moveTo(player.x + 25, player.y + 40);
        ctx.lineTo(player.x + 35, player.y + 50); // 左脚
        ctx.stroke();

        // 銃
        ctx.fillStyle = 'gray';
        ctx.fillRect(player.x + 5, player.y + 30, 10, 5);
    }
}

function drawEnemy() {//ボス
    ctx.strokeStyle = 'red';
    if (enemy.facingRight) {
        // 右向きの人型
        ctx.beginPath();
        ctx.arc(enemy.x + 25, enemy.y + 15, 10, 0, Math.PI * 2, true); // 頭
        ctx.moveTo(enemy.x + 25, enemy.y + 25);
        ctx.lineTo(enemy.x + 25, enemy.y + 40); // 体
        ctx.moveTo(enemy.x + 25, enemy.y + 30);
        ctx.lineTo(enemy.x + 35, enemy.y + 35); // 右腕
        ctx.moveTo(enemy.x + 25, enemy.y + 30);
        ctx.lineTo(enemy.x + 15, enemy.y + 35); // 左腕
        ctx.moveTo(enemy.x + 25, enemy.y + 40);
        ctx.lineTo(enemy.x + 35, enemy.y + 50); // 右脚
        ctx.moveTo(enemy.x + 25, enemy.y + 40);
        ctx.lineTo(enemy.x + 15, enemy.y + 50); // 左脚
        ctx.stroke();
    } else {
        // 左向きの人型
        ctx.beginPath();
        ctx.arc(enemy.x + 25, enemy.y + 15, 10, 0, Math.PI * 2, true); // 頭
        ctx.moveTo(enemy.x + 25, enemy.y + 25);
        ctx.lineTo(enemy.x + 25, enemy.y + 40); // 体
        ctx.moveTo(enemy.x + 25, enemy.y + 30);
        ctx.lineTo(enemy.x + 15, enemy.y + 35); // 右腕
        ctx.moveTo(enemy.x + 25, enemy.y + 30);
        ctx.lineTo(enemy.x + 35, enemy.y + 35); // 左腕
        ctx.moveTo(enemy.x + 25, enemy.y + 40);
        ctx.lineTo(enemy.x + 15, enemy.y + 50); // 右脚
        ctx.moveTo(enemy.x + 25, enemy.y + 40);
        ctx.lineTo(enemy.x + 35, enemy.y + 50); // 左脚
        ctx.stroke();
    }
}

function drawSpikes() {//針
    if (spikes.active) {
        ctx.fillStyle = 'red';
        const spikeWidth = 20; // 針の幅を大きくする
        const spikeHeight = 40; // 針の高さを大きくする
        for (let i = 0; i < canvas.width; i += spikeWidth) {
            ctx.beginPath();
            ctx.moveTo(i, spikes.y);
            ctx.lineTo(i + spikeWidth / 2, spikes.y - spikeHeight);
            ctx.lineTo(i + spikeWidth, spikes.y);
            ctx.closePath();
            ctx.fill();
        }
        // キャンバスの下部を背景色で塗りつぶす
        ctx.fillStyle = '#000';
        ctx.fillRect(0, spikes.y, canvas.width, canvas.height - spikes.y);
    }
}

function drawStars() {
    ctx.fillStyle = 'white';
    stars.forEach(star => {
        ctx.beginPath();
        ctx.arc(star.x, star.y, star.size, 0, Math.PI * 2);
        ctx.fill();
    });
}

function updateStars() {
    stars.forEach(star => {
        star.x -= star.speed;
        if (star.x < 0) {
            star.x = canvas.width;
            star.y = Math.random() * canvas.height;
        }
    });
}






function drawPlatform() {
    ctx.fillStyle = 'white';
    ctx.fillRect(platform.x, platform.y, platform.width, platform.height);
}

function drawBullets() {
    ctx.fillStyle = 'yellow';
    player.bullets.forEach((bullet, index) => {
        ctx.fillRect(bullet.x, bullet.y, bullet.width, bullet.height);
        bullet.x += bullet.speed;

        if (bullet.x > canvas.width || bullet.x < 0) {
            player.bullets.splice(index, 1);
        }

        if (bullet.x < enemy.x + enemy.width &&
            bullet.x + bullet.width > enemy.x &&
            bullet.y < enemy.y + enemy.height &&
            bullet.y + bullet.height > enemy.y) {
            player.bullets.splice(index, 1);
            enemy.life -= 1;
            if (enemy.life <= 0) {
                gameClear = true;
            }
        }
    });
}

function drawLifeBar() {
    ctx.fillStyle = 'black';
    ctx.fillRect(10, 10, 200, 20);
    ctx.fillStyle = 'green';
    ctx.fillRect(10, 10, 200 * (enemy.life / enemy.maxLife), 20);
    ctx.strokeStyle = 'white';
    ctx.strokeRect(10, 10, 200, 20);
    ctx.fillStyle = 'white';
    ctx.font = '16px Arial';
    ctx.fillText(`ライフ: ${enemy.life}`, 220, 25);
}

function drawStartScreen() {
    ctx.fillStyle = 'white';
    ctx.font = '30px Arial';
    ctx.fillText('エンターキーを押してゲームを開始', 150, 300);
}

function drawWaterZone() {
    ctx.fillStyle = 'rgba(0, 0, 255, 0.5)';
    ctx.beginPath();
    ctx.arc(waterZone.x + waterZone.radius, waterZone.y + waterZone.radius, waterZone.radius, 0, Math.PI * 2);
    ctx.fill();
}

function drawBouncingBall() {
    if (bouncingBall.active) {
        ctx.fillStyle = '#ff7f50';
        ctx.beginPath();
        ctx.arc(bouncingBall.x, bouncingBall.y, bouncingBall.radius, 0, Math.PI * 2);
        ctx.fill();
    }
    if (bouncingBall2.active) {
        ctx.fillStyle = '#ff7f50';
        ctx.beginPath();
        ctx.arc(bouncingBall2.x, bouncingBall2.y, bouncingBall2.radius, 0, Math.PI * 2);
        ctx.fill();
    }
    if (bouncingBall3.active) { // 新しい玉の描画
        ctx.fillStyle = '#ff7f50';
        ctx.beginPath();
        ctx.arc(bouncingBall3.x, bouncingBall3.y, bouncingBall3.radius, 0, Math.PI * 2);
        ctx.fill();
    }
}



function updateBouncingBall() {
    if (bouncingBall.active) {
        bouncingBall.x += bouncingBall.speedX;
        bouncingBall.y += bouncingBall.speedY;

        // 壁に当たったら反射
        if (bouncingBall.x + bouncingBall.radius > canvas.width || bouncingBall.x - bouncingBall.radius < 0) {
            bouncingBall.speedX *= -1;
        }
        if (bouncingBall.y + bouncingBall.radius > canvas.height || bouncingBall.y - bouncingBall.radius < 0) {
            bouncingBall.speedY *= -1;
        }

        // プレイヤーとの当たり判定
        const dx = bouncingBall.x - (player.x + player.width / 2);
        const dy = bouncingBall.y - (player.y + player.height / 2);
        const distance = Math.sqrt(dx * dx + dy * dy);

        if (distance < bouncingBall.radius + player.width / 2) {
            gameOver = true;
            bgm.pause(); // BGMを停止
            gameOverSound.currentTime = 0; // 再生位置をリセット
            gameOverSound.play().catch(error => {
                console.error('ゲームオーバー音の再生に失敗しました:', error);
            }); // ゲームオーバー音を再生
        }
    }

    if (bouncingBall2.active) {
        bouncingBall2.x += bouncingBall2.speedX;
        bouncingBall2.y += bouncingBall2.speedY;

        // 壁に当たったら反射
        if (bouncingBall2.x + bouncingBall2.radius > canvas.width || bouncingBall2.x - bouncingBall2.radius < 0) {
            bouncingBall2.speedX *= -1;
        }
        if (bouncingBall2.y + bouncingBall2.radius > canvas.height || bouncingBall2.y - bouncingBall2.radius < 0) {
            bouncingBall2.speedY *= -1;
        }

        // プレイヤーとの当たり判定
        const dx2 = bouncingBall2.x - (player.x + player.width / 2);
        const dy2 = bouncingBall2.y - (player.y + player.height / 2);
        const distance2 = Math.sqrt(dx2 * dx2 + dy2 * dy2);

        if (distance2 < bouncingBall2.radius + player.width / 2) {
            gameOver = true;
            bgm.pause(); // BGMを停止
            gameOverSound.currentTime = 0; // 再生位置をリセット
            gameOverSound.play().catch(error => {
                console.error('ゲームオーバー音の再生に失敗しました:', error);
            }); // ゲームオーバー音を再生
        }
    }

    if (bouncingBall3.active) { // 新しい玉の更新
        bouncingBall3.x += bouncingBall3.speedX;
        bouncingBall3.y += bouncingBall3.speedY;

        // 壁に当たったら反射
        if (bouncingBall3.x + bouncingBall3.radius > canvas.width || bouncingBall3.x - bouncingBall3.radius < 0) {
            bouncingBall3.speedX *= -1;
        }
        if (bouncingBall3.y + bouncingBall3.radius > canvas.height || bouncingBall3.y - bouncingBall3.radius < 0) {
            bouncingBall3.speedY *= -1;
        }

        // プレイヤーとの当たり判定
        const dx3 = bouncingBall3.x - (player.x + player.width / 2);
        const dy3 = bouncingBall3.y - (player.y + player.height / 2);
        const distance3 = Math.sqrt(dx3 * dx3 + dy3 * dy3);

        if (distance3 < bouncingBall3.radius + player.width / 2) {
            gameOver = true;
            bgm.pause(); // BGMを停止
            gameOverSound.currentTime = 0; // 再生位置をリセット
            gameOverSound.play().catch(error => {
                console.error('ゲームオーバー音の再生に失敗しました:', error);
            }); // ゲームオーバー音を再生
        }
    }
}




function drawEnemyBullets() {
    ctx.fillStyle = 'red';
    enemyBullets.forEach((bullet, index) => {
        ctx.fillRect(bullet.x, bullet.y, bullet.width, bullet.height);
        bullet.x -= bullet.speed;

        if (bullet.x < 0) {
            enemyBullets.splice(index, 1);
        }

        if (bullet.x < player.x + player.width &&
            bullet.x + bullet.width > player.x &&
            bullet.y < player.y + player.height &&
            bullet.y + bullet.height > player.y) {
            enemyBullets.splice(index, 1);
            if (!gameClear) { // ゲームクリアでない場合のみゲームオーバー処理を行う
                gameOver = true;
                bgm.pause(); // BGMを停止
                gameOverSound.currentTime = 0; // 再生位置をリセット
                gameOverSound.play().catch(error => {
                    console.error('ゲームオーバー音の再生に失敗しました:', error);
                }); // ゲームオーバー音を再生
            }
        }
    });
}



function drawGameOverScreen() {
    ctx.fillStyle = 'red';
    ctx.font = '50px Arial';
    ctx.fillText('ゲームオーバー', canvas.width / 2 - 150, canvas.height / 2);
}

function drawGameClearScreen() {
    ctx.fillStyle = 'green';
    ctx.font = '50px Arial';
    ctx.fillText('ゲームクリア', canvas.width / 2 - 150, canvas.height / 2);
}

function resetGame() {
    // プレイヤーの初期位置と状態をリセット
    player.x = canvas.width / 2 - 25;
    player.y = canvas.height - 60;
    player.bullets = [];
    player.isJumping = false;
    player.jumpSpeed = 0;
    player.moveLeft = false;
    player.moveRight = false;
    player.jumpCount = 0;
    player.facingRight = true;

    // 敵の初期位置と状態をリセット
    enemy.x = canvas.width - 75;
    enemy.y = canvas.height / 2 - 5 - 50;
    enemy.life = enemy.maxLife;

    // 敵の弾をリセット
    enemyBullets.length = 0;

    // 反射する球の初期状態をリセット
    bouncingBall.active = false;
    bouncingBall.x = enemy.x;
    bouncingBall.y = enemy.y;
    bouncingBall.speedX = 3;
    bouncingBall.speedY = 3;

    bouncingBall2.active = false;
    bouncingBall2.x = enemy.x;
    bouncingBall2.y = enemy.y;
    bouncingBall2.speedX = 3;
    bouncingBall2.speedY = 3;

    bouncingBall3.active = false; // 新しい玉のリセット
    bouncingBall3.x = enemy.x;
    bouncingBall3.y = enemy.y;
    bouncingBall3.speedX = 3;
    bouncingBall3.speedY = 3;

    // 針の初期状態をリセット
    spikes.active = false;

    // ゲームの状態をリセット
    gameOver = false;
    gameClear = false;
    gameStarted = false; // ゲームを初期状態に戻す

    // BGMを停止
    bgm.pause();
    bgm.currentTime = 0;

    // ゲームオーバー音とゲームクリア音を停止
    gameOverSound.pause();
    gameOverSound.currentTime = 0;
    gameClearSound.pause();
    gameClearSound.currentTime = 0;
}


function shootEnemyBullet() {
    const bulletSpeed = Math.random() * 3 + 2; // ランダムな速度
    enemyBullets.push({
        x: enemy.x,
        y: enemy.y + enemy.height / 2 - 2.5,
        width: 10,
        height: 5,
        speed: bulletSpeed
    });
}

// 一定の間隔で敵が弾を発射する
setInterval(shootEnemyBullet, 1000);

function update() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    if (!gameStarted) {
        drawStartScreen();
    } else if (gameOver) {
        drawGameOverScreen();
    } else if (gameClear) {
        drawGameClearScreen();
    } else {
        drawStars(); // 星を描画
        updateStars(); // 星を更新
        drawPlayer();
        drawPlatform();
        drawEnemy();
        drawWaterZone();
        drawBullets();
        drawLifeBar();
        drawEnemyBullets();
        drawBouncingBall(); // 反射する球を描画
        drawSpikes(); // 針を描画

        // プレイヤーのジャンプ処理
        if (player.isJumping) {
            player.y -= player.jumpSpeed;
            player.jumpSpeed -= player.inWater ? 0.2 : 0.5; // 水中ではゆっくりと減速
            if (player.y >= canvas.height - 60) {
                player.y = canvas.height - 60;
                player.isJumping = false;
                player.jumpSpeed = 0;
                player.jumpCount = 0; // ジャンプカウントをリセット
            }
        } else if (player.inWater) {
            player.y += 5; // 水中ではさらに遅い浮遊する感じの重力で下降する
        }

        // プレイヤーの移動処理
        if (player.moveLeft && player.x > 0) {
            player.x -= player.speed;
            player.facingRight = false;
        }
        if (player.moveRight && player.x < canvas.width - player.width) {
            player.x += player.speed;
            player.facingRight = true;
        }

        // 水中ゾーンの判定
        const dx = player.x + player.width / 2 - (waterZone.x + waterZone.radius);
        const dy = player.y + player.height / 2 - (waterZone.y + waterZone.radius);
        const distance = Math.sqrt(dx * dx + dy * dy);

        if (distance < waterZone.radius) {
            player.inWater = true;
            player.jumpCount = 1; // 水中に入ったらジャンプカウントを一回回復
            if (!spikes.active) { // 針がアクティブでない場合のみ効果音を再生
                spikes.active = true; // 針をアクティブにする
                hariSound.currentTime = 0;
                hariSound.play().catch(error => {
                    console.error('針の効果音の再生に失敗しました:', error);
                });
            }
        } else {
            player.inWater = false;
        }

        // プレイヤーが針に当たったらゲームオーバー
        if (spikes.active && player.y + player.height > spikes.y - 40) { // 針の高さに合わせて判定を調整
            gameOver = true;
            bgm.pause(); // BGMを停止
            gameOverSound.currentTime = 0; // 再生位置をリセット
            gameOverSound.play().catch(error => {
                console.error('ゲームオーバー音の再生に失敗しました:', error);
            }); // ゲームオーバー音を再生
        }

        // プレイヤーが敵の弾に当たったらゲームオーバー
        enemyBullets.forEach((bullet, index) => {
            if (bullet.x < player.x + player.width &&
                bullet.x + bullet.width > player.x &&
                bullet.y < player.y + player.height &&
                bullet.y + bullet.height > player.y) {
                enemyBullets.splice(index, 1);
                if (!gameClear) { // ゲームクリアでない場合のみゲームオーバー処理を行う
                    gameOver = true;
                    bgm.pause(); // BGMを停止
                    gameOverSound.currentTime = 0; // 再生位置をリセット
                    gameOverSound.play().catch(error => {
                        console.error('ゲームオーバー音の再生に失敗しました:', error);
                    }); // ゲームオーバー音を再生
                }
            }
        });

        // 敵のライフが80ポイント以下になったら新しい反射する球をアクティブにする
        if (enemy.life <= 80 && !bouncingBall3.active) {
            bouncingBall3.x = enemy.x + enemy.width / 2; // 敵の近くに出現
            bouncingBall3.y = enemy.y + enemy.height / 2;
            const angle3 = Math.random() * Math.PI * 2; // ランダムな角度
            bouncingBall3.speedX = 3 * Math.cos(angle3); // 一定のスピード
            bouncingBall3.speedY = 3 * Math.sin(angle3);
            bouncingBall3.active = true;
            hariSound.currentTime = 0;
            hariSound.play().catch(error => {
                console.error('針の効果音の再生に失敗しました:', error);
            });
        }

        // 敵のライフが50ポイント以下になったら反射する球をアクティブにする
        if (enemy.life <= 50 && !bouncingBall.active) {
            bouncingBall.x = enemy.x + enemy.width / 2; // 敵の近くに出現
            bouncingBall.y = enemy.y + enemy.height / 2;
            const angle1 = Math.random() * Math.PI * 2; // ランダムな角度
            bouncingBall.speedX = 3 * Math.cos(angle1); // 一定のスピード
            bouncingBall.speedY = 3 * Math.sin(angle1);
            bouncingBall.active = true;
            hariSound.currentTime = 0;
            hariSound.play().catch(error => {
                console.error('針の効果音の再生に失敗しました:', error);
            });
        }

        // 敵のライフが20ポイント以下になったらもう一つの反射する球をアクティブにする
        if (enemy.life <= 20 && !bouncingBall2.active) {
            bouncingBall2.x = enemy.x + enemy.width / 2; // 敵の近くに出現
            bouncingBall2.y = enemy.y + enemy.height / 2;
            const angle2 = Math.random() * Math.PI * 2; // ランダムな角度
            bouncingBall2.speedX = 3 * Math.cos(angle2); // 一定のスピード
            bouncingBall2.speedY = 3 * Math.sin(angle2);
            bouncingBall2.active = true;
            hariSound.currentTime = 0;
            hariSound.play().catch(error => {
                console.error('針の効果音の再生に失敗しました:', error);
            });
        }

        // 敵のライフが0になったらゲームクリア
        if (enemy.life <= 0 && !gameOver) {
            gameClear = true;
            bgm.pause(); // BGMを停止
            gameClearSound.currentTime = 0; // 再生位置をリセット
            gameClearSound.play().catch(error => {
                console.error('ゲームクリア音の再生に失敗しました:', error);
            }); // ゲームクリア音を再生
        }

        updateBouncingBall(); // 反射する球の動きを更新
    }
}







function gameLoop() {
    update();
    requestAnimationFrame(gameLoop);
}

document.addEventListener('keydown', (e) => {
    if (!gameStarted && e.key === 'Enter') {
        gameStarted = true;
        bgm.play().catch(error => {
            console.error('BGMの再生に失敗しました:', error);
        }); // ゲームが始まるときにBGMを再生
    } else if (e.key === 'a') { // 左移動を'a'キーに変更
        player.moveLeft = true;
    } else if (e.key === 's') { // 右移動を's'キーに変更
        player.moveRight = true;
    } else if (e.key === ' ' && player.jumpCount < 2) {
        player.isJumping = true;
        player.jumpSpeed = player.inWater ? 3 : 10; // 水中ではジャンプ速度を高くする
        player.jumpCount++;
        
        // ジャンプ音を再生
        if (player.jumpCount === 1) {
            jumpSound1.currentTime = 0;
            jumpSound1.play().catch(error => {
                console.error('ジャンプ音1の再生に失敗しました:', error);
            });
        } else if (player.jumpCount === 2) {
            jumpSound2.currentTime = 0;
            jumpSound2.play().catch(error => {
                console.error('ジャンプ音2の再生に失敗しました:', error);
            });
        }
    } else if (e.key === 'z' && canShoot) {
        player.bullets.push({
            x: player.facingRight ? player.x + player.width : player.x,
            y: player.y + player.height / 2 - 2.5,
            width: 10,
            height: 5,
            speed: player.facingRight ? 7 : -7
        });
        canShoot = false;
        shootSound.currentTime = 0; // 再生位置をリセット
        shootSound.play().catch(error => {
            console.error('効果音の再生に失敗しました:', error);
        }); // 効果音を再生
    } else if (e.key === 'r' && (gameOver || gameClear)) {
        resetGame(); // ゲームをリスタート
    }
});


document.addEventListener('keyup', (e) => {
    if (e.key === 'a') { // 左移動を'a'キーに変更
        player.moveLeft = false;
    } else if (e.key === 's') { // 右移動を's'キーに変更
        player.moveRight = false;
    } else if (e.key === 'z') {//打つ
        canShoot = true;
    }
});


gameLoop();