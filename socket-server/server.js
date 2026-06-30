const https = require("https");
const http = require("http");
const fs = require("fs");
const { Server } = require("socket.io");
const { handleStep } = require("./StepEvent");
require("dotenv").config();

const PORT = process.env.SOCKET_PORT || 3000;
const CERT_DIR = "/etc/letsencrypt/live/www.wernapp.com";

let server;
let scheme;
if (fs.existsSync(`${CERT_DIR}/privkey.pem`) && fs.existsSync(`${CERT_DIR}/fullchain.pem`)) {
  server = https.createServer({
    key:  fs.readFileSync(`${CERT_DIR}/privkey.pem`),
    cert: fs.readFileSync(`${CERT_DIR}/fullchain.pem`)
  });
  scheme = "HTTPS";
} else {
  server = http.createServer();
  scheme = "HTTP (no SSL cert found at " + CERT_DIR + ")";
}

const io = new Server(server, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"]
  }
});

server.listen(PORT, () => {
  console.log(`✅ Socket.IO running on ${scheme} port ${PORT}`);
});

io.on("connection", (socket) => {
  console.log("🔗 Client connected:", socket.id);

  socket.on("step_event", async (data) => {
    try {
      await handleStep(socket, data);
      // socket.emit("step_ack", {
      //   status: true,
      //   message: "Step event processed",
      //   data
      // });
    } catch (err) {
      console.error("❌ STEP ERROR:", err.message);
      socket.emit("server_error", {
        status: false,
        message: err.message
      });
    }
  });

  socket.on("disconnect", () => {
    console.log("❌ Client disconnected:", socket.id);
  });
});