document.addEventListener('DOMContentLoaded', function () {
  const timeInput = document.querySelector('input[name="total_time"]');

  timeInput.addEventListener('input', function (e) {
    let value = e.target.value.replace(/[^0-9]/g, '');

    if (value.length > 4) {
      value = value.slice(0, 4);
    }

    if (value.length > 2) {
      value = value.slice(0, 2) + ':' + value.slice(2);
    }

    e.target.value = value;
  });
});
