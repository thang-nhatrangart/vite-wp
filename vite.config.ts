import { defineConfig, splitVendorChunkPlugin } from "vite";
import react from "@vitejs/plugin-react";
import path from "path";
import fs from "fs";

const rootpath = "./src";
function getTopLevelFiles(): Record<string, string> {
  let topLevelFiles = fs.readdirSync(path.resolve(__dirname, rootpath));
  let files: { [key: string]: string } = {};
  topLevelFiles.forEach((file) => {
    const isFile = fs.lstatSync(path.resolve(rootpath, file)).isFile();
    if (isFile && !file.includes(".d.ts")) {
      const chunkName = file.slice(0, file.lastIndexOf("."));
      files[chunkName] = path.resolve(rootpath, file);
    }
  });
  return files;
}

// https://vitejs.dev/config/
export default defineConfig({
  root: rootpath,
  base: "/",
  plugins: [react()],
  build: {
    manifest: true,
    emptyOutDir: true,
    outDir: path.resolve("../assets", "dist1"),
    assetsDir: "",
    rollupOptions: {
      input: getTopLevelFiles(),
    },
  },
  server: {
    cors: true,
    strictPort: true,
    port: 3000,
    hmr: {
      port: 3000,
      host: "localhost",
      protocol: "ws",
    },
  },
});
