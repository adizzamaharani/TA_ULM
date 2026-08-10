const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

async function captureWireframe(url, role, outputPath, preClickSelector = null) {
    console.log("Capturing: " + outputPath);
    const browser = await puppeteer.launch({headless: "new"});
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 900 });

    if (role) {
        await page.goto('http://127.0.0.1:8000/wireframer/login_as.php?role=' + role);
        await page.waitForSelector('body');
    }

    await page.goto(url, {waitUntil: 'networkidle0'});
    if (preClickSelector) {
        try {
            await page.click(preClickSelector);
            await new Promise(r => setTimeout(r, 1000));
        } catch (e) {
            console.log("Could not click " + preClickSelector);
        }
    }

    await page.addStyleTag({content: `
        * {
            background-color: #fff !important;
            color: #444 !important;
            border-color: #aaa !important;
            box-shadow: none !important;
            text-shadow: none !important;
            border-radius: 2px !important;
            background-image: none !important;
        }
        button, .btn {
            border: 2px solid #555 !important;
            background: #eee !important;
            font-weight: bold !important;
        }
        input, select, textarea, .card, table, th, td {
            border: 1px solid #777 !important;
            background: #fafafa !important;
        }
        img, svg, canvas {
            opacity: 0.1 !important;
            border: 2px dashed #999 !important;
            background: #eee !important;
        }
    `});

    const dir = path.dirname(outputPath);
    if (!fs.existsSync(dir)){
        fs.mkdirSync(dir, { recursive: true });
    }

    await page.screenshot({ path: outputPath, fullPage: true });
    await browser.close();
    console.log("Done: " + outputPath);
}

(async () => {
    try {
        // Admin
        await captureWireframe('http://127.0.0.1:8000/admin/statistik.php', 'admin', '../wireframe/Admin/Keluaran/statistik.png');
        await captureWireframe('http://127.0.0.1:8000/admin/backup_restore.php', 'admin', '../wireframe/Admin/Masukan/backup_restore.png');

        console.log("Admin additional wireframes generated successfully!");
    } catch (e) {
        console.error("Error:", e);
    }
})();
