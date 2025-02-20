const puppeteer = require('puppeteer');
const fs = require('fs');

(async () => {
    // Get the parameter from the command-line arguments
    const url = process.argv[2];
// console.log(url);
    const browser = await puppeteer.launch({
        executablePath: '/usr/bin/chromium-browser',
        headless: true, // or false if you want to see the browser
        args: ['--no-sandbox', '--disable-setuid-sandbox'] // Important!
    });
    const page = await browser.newPage();

    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3');
    await page.goto(url, { waitUntil: 'networkidle2' });
    // await page.waitForTimeout(5000);
    const content = await page.content();
    console.log(content);
    await browser.close();
})();