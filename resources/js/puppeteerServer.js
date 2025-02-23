import puppeteer from "puppeteer-extra";
import StealthPlugin from "puppeteer-extra-plugin-stealth";
import { WebSocketServer } from "ws";

const wss = new WebSocketServer({ port: 8080 });

console.log("WebSocket server running on ws://localhost:8080");

wss.on("connection", (ws) => {
    console.log("Client connected");

    puppeteer.use(StealthPlugin());

    ws.on("message", async (message) => {
        const url = message.toString();
        console.log(`Received request to scrape: ${url}`);

        try {
            const browser = await puppeteer.launch({
                // executablePath: '/usr/bin/chromium-browser',
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

            await browser.close();

            // ws.send(`Title: ${title}`); // Send result back to client
            ws.send(`${content}`); // Send result back to client
        } catch (error) {
            ws.send(`Error: ${error.message}`);
        }
    });
});
