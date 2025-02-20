import { exec } from 'child_process';

async function puppeteerClient(url) {
    return new Promise((resolve, reject) => {
        exec(`node crawl.js "${url}"`, (error, stdout, stderr) => {
            if (error) {
                reject(`Error: ${stderr || error.message}`);
                return;
            }
            resolve(stdout.trim()); // Return the scraped title
        });
    });
}

// Expose function globally
globalThis.puppeteerClient = puppeteerClient;