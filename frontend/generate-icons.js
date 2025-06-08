import sharp from 'sharp';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Create a simple forum icon using SVG
const createForumSVG = (size) => {
  return `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" xmlns="http://www.w3.org/2000/svg">
    <defs>
      <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
        <stop offset="100%" style="stop-color:#1e40af;stop-opacity:1" />
      </linearGradient>
    </defs>
    <rect width="100%" height="100%" fill="url(#grad)" rx="${size * 0.15}"/>
    <g transform="translate(${size * 0.15}, ${size * 0.15})">
      <!-- Forum icon - conversation bubbles -->
      <rect x="${size * 0.1}" y="${size * 0.1}" width="${size * 0.4}" height="${size * 0.25}" fill="white" rx="${size * 0.05}" opacity="0.9"/>
      <rect x="${size * 0.25}" y="${size * 0.25}" width="${size * 0.4}" height="${size * 0.25}" fill="white" rx="${size * 0.05}" opacity="0.8"/>
      <rect x="${size * 0.15}" y="${size * 0.4}" width="${size * 0.4}" height="${size * 0.25}" fill="white" rx="${size * 0.05}" opacity="0.7"/>
      
      <!-- Text lines -->
      <rect x="${size * 0.15}" y="${size * 0.17}" width="${size * 0.25}" height="${size * 0.02}" fill="#1e40af" opacity="0.7"/>
      <rect x="${size * 0.15}" y="${size * 0.22}" width="${size * 0.2}" height="${size * 0.02}" fill="#1e40af" opacity="0.5"/>
      
      <rect x="${size * 0.3}" y="${size * 0.32}" width="${size * 0.25}" height="${size * 0.02}" fill="#1e40af" opacity="0.7"/>
      <rect x="${size * 0.3}" y="${size * 0.37}" width="${size * 0.2}" height="${size * 0.02}" fill="#1e40af" opacity="0.5"/>
      
      <rect x="${size * 0.2}" y="${size * 0.47}" width="${size * 0.25}" height="${size * 0.02}" fill="#1e40af" opacity="0.7"/>
      <rect x="${size * 0.2}" y="${size * 0.52}" width="${size * 0.2}" height="${size * 0.02}" fill="#1e40af" opacity="0.5"/>
    </g>
  </svg>`;
};

async function generateIcons() {
  const iconsDir = path.join(__dirname, 'public', 'icons');
  
  // Ensure icons directory exists
  if (!fs.existsSync(iconsDir)) {
    fs.mkdirSync(iconsDir, { recursive: true });
  }

  const sizes = [72, 96, 128, 144, 152, 192, 384, 512];

  for (const size of sizes) {
    const svgContent = createForumSVG(size);
    const svgBuffer = Buffer.from(svgContent);
    
    try {
      await sharp(svgBuffer)
        .resize(size, size)
        .png()
        .toFile(path.join(iconsDir, `icon-${size}x${size}.png`));
      
      console.log(`✓ Generated icon-${size}x${size}.png`);
    } catch (error) {
      console.error(`✗ Failed to generate icon-${size}x${size}.png:`, error.message);
    }
  }

  // Generate favicon
  try {
    const svgContent = createForumSVG(32);
    const svgBuffer = Buffer.from(svgContent);
    
    await sharp(svgBuffer)
      .resize(32, 32)
      .png()
      .toFile(path.join(__dirname, 'public', 'favicon.png'));
    
    console.log('✓ Generated favicon.png');
  } catch (error) {
    console.error('✗ Failed to generate favicon.png:', error.message);
  }

  console.log('\nAll PWA icons generated successfully! 🎉');
}

generateIcons().catch(console.error);
