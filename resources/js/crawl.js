import puppeteer from 'puppeteer';

(async () => {
    const url = process.argv[2]; // Get URL from arguments
    console.error(url)
    if (!url) {
        console.error('No URL provided');
        process.exit(1);
    }

    try {
        const browser = await puppeteer.launch({
            executablePath: '/usr/bin/chromium-browser',
            headless: true, // or false if you want to see the browser
            args: ['--no-sandbox', '--disable-setuid-sandbox'] // Important!
        });


        const page = await browser.newPage();

        await page.setUserAgent(
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
        );

        await page.setExtraHTTPHeaders({
            "Accept-Language": "en-US,en;q=0.9",
            "Connection": "keep-alive",
        });
        await page.goto(url, { waitUntil: "domcontentloaded" });
        // await page.goto(url, { waitUntil: 'networkidle2' });

        // const title = await page.title();

        const content = await page.content();
        console.log(content);
        await browser.close();

    } catch (error) {
        console.error('Error:', error.message);
        console.error(url)
        process.exit(1);
    }
})();