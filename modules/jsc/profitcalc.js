const checkboxes = document.querySelectorAll('input[name="profitcalc"]');
const profitCalcContainer = document.getElementById('profitcalccontainer');


function updateProfitSum() {
    let totalSum = 0;
    checkboxes.forEach((checkbox) => {
        if (checkbox.checked) {
            totalSum += parseFloat(checkbox.getAttribute('pfstc')) || 0;
        }
    });

    if (totalSum > 0) {
        profitCalcContainer.textContent = totalSum.toFixed(2);
        profitCalcContainer.classList.add('wehaveprofit');
        profitCalcContainer.style.display = 'block';
    } else {
        profitCalcContainer.textContent = '';
        profitCalcContainer.classList.remove('wehaveprofit');
        profitCalcContainer.style.display = 'none';
    }
}

checkboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', updateProfitSum);
});