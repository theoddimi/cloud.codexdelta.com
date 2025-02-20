import puppeteer from 'puppeteer';

(async () => {
    const url = process.argv[2]; // Get URL from arguments
    if (!url) {
        console.error('No URL provided');
        process.exit(1);
    }

    try {
        const browser = await puppeteer.launch({
            // executablePath: '/usr/bin/chromium-browser',
            headless: true, // or false if you want to see the browser
            args: ['--no-sandbox', '--disable-setuid-sandbox'] // Important!
        });

        const page = await browser.newPage();
        await page.goto(url);

        const title = await page.title();
        console.log(title); // Send result back to parent process

        await browser.close();
    } catch (error) {
        console.error('Error:', error.message);
        process.exit(1);
    }
})();