import express from "express";
import { exec } from "child_process";

const app = express();
const PORT = 8080;

// API route to run your script
app.post("/run-crawl", (req, res) => {
    const inputData = req.body;
    exec(`node ./resources/js/crawl.js '${JSON.stringify(inputData)}'`, (error, stdout, stderr) => {
        if (error) {
            console.error(`Error: ${stderr}`);
            return res.status(500).json({ success: false, error: stderr });
        }
        res.json({ success: true, output: stdout });
    });
});

// Start the server
app.listen(PORT, () => {
    console.log(`Server running at http://localhost:${PORT}`);
});
