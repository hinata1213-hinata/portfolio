// ローディング画面
window.addEventListener('load', () => {
  setTimeout(() => {
    document.getElementById('loadingScreen').style.opacity = '0';
    setTimeout(() => {
      document.getElementById('loadingScreen').style.display = 'none';
    }, 500);
  }, 2000);
});

// パーティクル生成
function createParticle() {
  const particle = document.createElement('div');
  particle.className = 'particle';
  particle.style.left = Math.random() * 100 + '%';
  particle.style.animationDelay = Math.random() * 6 + 's';
  particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
  document.getElementById('particles').appendChild(particle);

  setTimeout(() => {
    particle.remove();
  }, 6000);
}

// パーティクルを定期的に生成
setInterval(createParticle, 200);

// 青い雨エフェクト（ホラー演出）
function createBlueRain() {
  const rain = document.createElement('div');
  rain.className = 'blood-rain';
  rain.style.left = Math.random() * 100 + '%';
  rain.style.animationDuration = (Math.random() * 2 + 2) + 's';
  rain.style.animationDelay = Math.random() * 2 + 's';
  rain.style.opacity = Math.random() * 0.5 + 0.3;
  document.body.appendChild(rain);

  setTimeout(() => rain.remove(), 5000);
}

// 青い雨を定期的に生成
setInterval(createBlueRain, 300);

// 音響効果（ホバー音）
function playHoverSound() {
  const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHzH8N2QQAoUXrTp66hVFApGn+DyvmMeRDSJzvfQdSMFLIPD9NJ6NQUsOsHyuGsaDgqFkdfRgiUFRZ3C5sNmIgU/ks3Ih');
  audio.volume = 0.1;
  audio.play().catch(() => {});
}

// 環境音効果
let ambientSound = false;
function toggleAmbientSound() {
  if (!ambientSound) {
    ambientSound = true;
    document.body.style.filter = 'contrast(1.1) brightness(0.95)';
  } else {
    ambientSound = false;
    document.body.style.filter = '';
  }
}

// ヘッダースクロール効果
window.addEventListener('scroll', () => {
  const header = document.getElementById('header');
  if (window.scrollY > 100) {
    header.classList.add('scrolled');
  } else {
    header.classList.remove('scrolled');
  }
  
  revealOnScroll();
});

// スクロール連動表示
function revealOnScroll() {
  const reveals = document.querySelectorAll('.scroll-reveal');
  reveals.forEach(element => {
    const windowHeight = window.innerHeight;
    const elementTop = element.getBoundingClientRect().top;
    const elementVisible = 150;
    
    if (elementTop < windowHeight - elementVisible) {
      element.classList.add('revealed');
    }
  });
}

// 青い雫エフェクト
function createBlueDrip() {
  const drip = document.createElement('div');
  drip.className = 'blood-drip';
  drip.style.left = Math.random() * 100 + '%';
  document.body.appendChild(drip);

  setTimeout(() => drip.remove(), 3000);
}

// ホラー効果の追加
function addHorrorEffects() {
  // ランダムな青い雫
  if (Math.random() < 0.3) {
    createBlueDrip();
  }

  // ランダムなグリッチ
  const glitchElements = document.querySelectorAll('.horror-text');
  glitchElements.forEach(element => {
    if (Math.random() < 0.1) {
      element.style.animation = 'glitch 0.3s';
      setTimeout(() => {
        element.style.animation = 'horror-flicker 3s infinite';
      }, 300);
    }
  });
}

// ホラー効果を定期実行
setInterval(addHorrorEffects, 5000);

// マウスホバー音効果
document.querySelectorAll('.torii-card, .feature-card, .timeline-item').forEach(element => {
  element.addEventListener('mouseenter', playHoverSound);
});

// キーボードショートカット
document.addEventListener('keydown', (e) => {
  // Spaceキーで環境音切り替え
  if (e.code === 'Space' && e.target.tagName !== 'INPUT') {
    e.preventDefault();
    toggleAmbientSound();
  }
  
  // Escキーでモーダル閉じる
  if (e.code === 'Escape') {
    closeModal();
  }
  
  // 数字キー1-6で該当する刻へスクロール
  if (e.code >= 'Digit1' && e.code <= 'Digit6') {
    const index = parseInt(e.code.slice(-1)) - 1;
    const cards = document.querySelectorAll('.torii-card');
    if (cards[index]) {
      cards[index].scrollIntoView({ behavior: 'smooth' });
    }
  }
});

// スムーススクロール
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute('href'));
    if (target) {
      target.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  });
});

// モーダル機能
function openModal(content) {
  const modal = document.getElementById('infoModal');
  const modalContent = document.getElementById('modalContent');
  modalContent.textContent = content;
  modal.classList.add('active');
}

function closeModal() {
  const modal = document.getElementById('infoModal');
  modal.classList.remove('active');
}

// モーダル外クリックで閉じる
document.getElementById('infoModal').addEventListener('click', (e) => {
  if (e.target.id === 'infoModal') {
    closeModal();
  }
});

// ランダムグリッチエフェクト
setInterval(() => {
  const elements = document.querySelectorAll('.torii-number');
  const randomElement = elements[Math.floor(Math.random() * elements.length)];
  if (randomElement && Math.random() < 0.1) {
    randomElement.style.animation = 'glitch 0.5s';
    setTimeout(() => {
      randomElement.style.animation = '';
    }, 500);
  }
}, 3000);

// 追加スタイル（青い雨用）
const additionalStyles = `
  @keyframes bloodRainFall {
    0% {
      top: -10%;
      opacity: 0;
    }
    10% {
      opacity: 1;
    }
    90% {
      opacity: 0.8;
    }
    100% {
      top: 110%;
      opacity: 0;
    }
  }

  .blood-rain {
    position: fixed;
    width: 2px;
    height: 30px;
    background: linear-gradient(180deg, transparent, #003D82, #0066CC);
    animation: bloodRainFall 3s linear;
    pointer-events: none;
    z-index: 5;
    filter: blur(0.5px);
  }
  
  @keyframes bounceIn {
    0% { transform: translate(-50%, -50%) scale(0.3); opacity: 0; }
    50% { transform: translate(-50%, -50%) scale(1.05); }
    70% { transform: translate(-50%, -50%) scale(0.9); }
    100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
  }
  
  @keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; }
  }
  
  @keyframes breathe {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.02); }
  }
  
  .breathing {
    animation: breathe 4s ease-in-out infinite;
  }
  
  /* モバイル最適化 */
  @media (max-width: 768px) {
    .timeline::before { display: none; }
    .timeline-content { width: 100% !important; margin: 0 !important; }
    .timeline-dot { left: 20px; }
    .timeline-item:nth-child(odd) .timeline-content,
    .timeline-item:nth-child(even) .timeline-content {
      margin-left: 50px;
    }
    
    .features-grid { grid-template-columns: 1fr; }
    .hero-title { font-size: 2.5rem; }
    .story-text { font-size: 1.1rem; }
  }
  
  /* アクセシビリティ */
  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
      animation-duration: 0.01ms !important;
      animation-iteration-count: 1 !important;
      transition-duration: 0.01ms !important;
    }
  }
  
  /* 高コントラストモード */
  @media (prefers-contrast: high) {
    .torii-card, .feature-card, .timeline-content {
      border-width: 3px;
      border-color: #0066CC;
    }
  }
  
  /* 鳥居カードを互い違いに配置 */
  .torii-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
  }
  
  .torii-card:nth-child(odd) {
    transform: translateY(-20px);
  }
  
  .torii-card:nth-child(even) {
    transform: translateY(20px);
  }
  
  .torii-card:hover {
    transform: translateY(-30px) scale(1.05) !important;
  }
  
  .torii-card:nth-child(even):hover {
    transform: translateY(10px) scale(1.05) !important;
  }
  
  /* モバイルでは通常配置 */
  @media (max-width: 768px) {
    .torii-grid {
      grid-template-columns: 1fr;
      gap: 20px;
    }
    
    .torii-card:nth-child(odd),
    .torii-card:nth-child(even) {
      transform: translateY(0);
    }
    
    .torii-card:hover {
      transform: translateY(-10px) scale(1.02) !important;
    }
  }
`;

const additionalStyleSheet = document.createElement('style');
additionalStyleSheet.textContent = additionalStyles;
document.head.appendChild(additionalStyleSheet);

// タイトルセクション背景画像切り替え機能
const titleBackgrounds = [
  'Images/30.png',
  'Images/44.png',
  'Images/55.png'
];
let currentBgIndex = 0;
let bgLayer1 = null;
let bgLayer2 = null;
let isLayer1Active = true;

// 画像をプリロード
function preloadImages() {
  titleBackgrounds.forEach(src => {
    const img = new Image();
    img.src = src;
  });
}

// 背景レイヤーを初期化
function initBgLayers() {
  const titleSection = document.querySelector('.title-section');
  if (!titleSection) return;

  // 元のbackground-imageを無効化
  titleSection.style.setProperty('background-image', 'none', 'important');

  // 共通スタイル
  const layerStyle = `
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center center;
    background-repeat: no-repeat;
    transition: opacity 1.5s ease-in-out;
    z-index: 0;
  `;

  // レイヤー1（初期表示）
  bgLayer1 = document.createElement('div');
  bgLayer1.className = 'bg-layer bg-layer-1';
  bgLayer1.style.cssText = layerStyle;
  bgLayer1.style.backgroundImage = `url('${titleBackgrounds[0]}')`;
  bgLayer1.style.opacity = '1';

  // レイヤー2（切り替え用）
  bgLayer2 = document.createElement('div');
  bgLayer2.className = 'bg-layer bg-layer-2';
  bgLayer2.style.cssText = layerStyle;
  bgLayer2.style.opacity = '0';

  // title-sectionの最初に挿入
  titleSection.insertBefore(bgLayer2, titleSection.firstChild);
  titleSection.insertBefore(bgLayer1, titleSection.firstChild);
}

function changeTitleBackground() {
  if (!bgLayer1 || !bgLayer2) return;

  // 次の背景インデックス
  currentBgIndex = (currentBgIndex + 1) % titleBackgrounds.length;
  const nextImage = `url('${titleBackgrounds[currentBgIndex]}')`;

  if (isLayer1Active) {
    // レイヤー2に新しい画像を設定してフェードイン
    bgLayer2.style.backgroundImage = nextImage;
    bgLayer2.style.opacity = '1';
    bgLayer1.style.opacity = '0';
  } else {
    // レイヤー1に新しい画像を設定してフェードイン
    bgLayer1.style.backgroundImage = nextImage;
    bgLayer1.style.opacity = '1';
    bgLayer2.style.opacity = '0';
  }

  isLayer1Active = !isLayer1Active;
}

// DOMロード後に初期化
document.addEventListener('DOMContentLoaded', () => {
  preloadImages();
  initBgLayers();
});

// 5秒ごとに背景を切り替え
setInterval(changeTitleBackground, 5000);

// 最終初期化処理
document.addEventListener('DOMContentLoaded', () => {
  // 呼吸アニメーションを一部要素に適用
  setTimeout(() => {
    document.querySelectorAll('.torii-card').forEach((card, index) => {
      if (index % 2 === 0) {
        card.classList.add('breathing');
      }
    });
  }, 2000);
  
  // 初期スクロール位置の調整
  if (window.location.hash) {
    setTimeout(() => {
      const target = document.querySelector(window.location.hash);
      if (target) {
        target.scrollIntoView({ behavior: 'smooth' });
      }
    }, 100);
  }
});

