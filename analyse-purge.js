const { PurgeCSS } = require('purgecss');
const fs = require('fs');
const path = require('path');
const config = require('./purgecss.config.js');

async function runAnalysis() {
  console.log('Starting PurgeCSS analysis...');
  
  const purgecssResult = await new PurgeCSS().purge(config);
  
  if (purgecssResult.length > 0) {
    const result = purgecssResult[0];
    const outputDir = config.output;

    // Ensure directory exists
    if (!fs.existsSync(outputDir)) {
      fs.mkdirSync(outputDir, { recursive: true });
    }

    // 1. Write the cleaned CSS (style.css)
    const survivedPath = path.join(outputDir, 'style.css');
    fs.writeFileSync(survivedPath, result.css);
    console.log(`✅ Survived CSS written to: ${survivedPath}`);

    // 2. Write the rejected selectors (rejected.css)
    if (result.rejected && result.rejected.length > 0) {
      const rejectedPath = path.join(outputDir, 'rejected.css');
      // result.rejected is an array of strings. We format them for readability.
      const formattedRejected = result.rejected.join('\n');
      fs.writeFileSync(rejectedPath, formattedRejected);
      console.log(`❌ Rejected ${result.rejected.length} selectors. List written to: ${rejectedPath}`);
    } else {
      console.log('⚠️ No selectors were rejected. Check your safelist or content paths.');
    }
  }
}

runAnalysis().catch(err => {
  console.error('PurgeCSS Error:', err);
});
