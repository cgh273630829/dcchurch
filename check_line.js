function isLineBrowser() {
    return /Line/i.test(navigator.userAgent);
}

function checkBrowser() {
    if (!isLineBrowser()) {
        document.body.innerHTML = '<h2>此網頁只能在 LINE 瀏覽器中使用。</h2>';
        // 或者可以選擇隱藏整個內容
        // document.body.style.display = 'none';
    }
}

// 使用 DOMContentLoaded 事件確保在 HTML 完全加載後執行檢查
document.addEventListener('DOMContentLoaded', (event) => {
    checkBrowser();
});