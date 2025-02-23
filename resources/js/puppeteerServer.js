import express from "express";
import { exec } from "child_process";

const app = express();
const PORT = 8080;

// app.use(cors({
//     origin: "*", // Allow all origins (*), or use "https://subdomain.domain.com"
//     methods: ["GET", "POST"],
//     allowedHeaders: ["Content-Type"]
// }));

app.use(express.json());

// Debugging: Log all incoming requests
// app.use((req, res, next) => {
//     console.log(`Received ${req.method} request on ${req.url}`);
//     console.log("Headers:", req.headers);
//     console.log("Body:", req.body);
//     next();
// });


// API route to run your script
app.post("/run-crawl", (req, res) => {
    const inputData = req.body.crawl_url;
    // const inputData = 'https://shybonsai.gr';
console.error('hello', inputData, `node ./resources/js/crawl.js ${inputData}`)
    exec(`node ./resources/js/crawl.js ${inputData}`, (error, stdout, stderr) => {
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
