import express from "express";
import { exec } from "child_process";

const app = express();
const PORT = 8080;

// API route to run your script
app.get("/my-script", (req, res) => {
    exec("node ./resources/js/crawl.js", (error, stdout, stderr) => {
        if (error) {
            console.error(`Error: ${stderr}`);
            return res.status(500).json({ success: false, error: stderr });
        }
        res.json({ success: true, output: stdout });
    });
});

// Start the server
app.listen(PORT, () => {
    console.log(`Server runningc at http://localhost:${PORT}`);
});
