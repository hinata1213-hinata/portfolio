let goodBtn = document.querySelectorAll('.js-good-btn');
let countTxt = document.querySelectorAll('.count-txt')

let count = {};

for(let i = 0; i < goodBtn.length; i++) {
  count[i] = 0;
  btnAll[i].addEventListener('click' , (e) => {
    count[i] ++;
    countTxt[i].textContent = count[i];
  });
}