#!/usr/bin/env node
'use strict';

const fs = require('fs');
const path = require('path');
const QRCode = require('qrcode');

const args = process.argv.slice(2);

const getArgValue = (name) => {
  const index = args.indexOf(name);
  if (index === -1 || index + 1 >= args.length) {
    return '';
  }
  return String(args[index + 1] || '');
};

const outPath = getArgValue('--out');
const dataBase64 = getArgValue('--data-base64');
const sizeRaw = getArgValue('--size');
const size = Number.parseInt(sizeRaw || '760', 10);

if (!outPath) {
  console.error('Parametro obrigatorio ausente: --out');
  process.exit(2);
}

if (!dataBase64) {
  console.error('Parametro obrigatorio ausente: --data-base64');
  process.exit(2);
}

if (!Number.isFinite(size) || size < 200 || size > 1200) {
  console.error('Parametro invalido: --size precisa estar entre 200 e 1200');
  process.exit(2);
}

let payload = '';
try {
  payload = Buffer.from(dataBase64, 'base64').toString('utf8');
} catch (error) {
  console.error('Falha ao decodificar payload base64.');
  process.exit(2);
}

if (!payload) {
  console.error('Payload QR vazio apos decodificacao.');
  process.exit(2);
}

(async () => {
  const absoluteOutPath = path.resolve(outPath);
  const outDir = path.dirname(absoluteOutPath);

  fs.mkdirSync(outDir, { recursive: true });

  await QRCode.toFile(absoluteOutPath, payload, {
    type: 'png',
    width: size,
    margin: 2,
    errorCorrectionLevel: 'M',
    color: {
      dark: '#000000',
      light: '#FFFFFF',
    },
  });
})()
  .then(() => {
    process.stdout.write('ok');
  })
  .catch((error) => {
    const message = error && error.message ? error.message : String(error);
    console.error(message);
    process.exit(1);
  });
