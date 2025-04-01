# Workerman-Connection-Pipe 示例

本目录包含了 workerman-connection-pipe 库的各种使用示例，用于展示不同类型的连接管道如何工作。

## 目录结构

- `server/` - 服务器端示例
- `client/` - 客户端示例

## 服务器示例

### TCP 到 TCP 转发

将 TCP 连接的数据转发到另一个 TCP 服务，类似于 TCP 代理。

```bash
php server/tcp_to_tcp_server.php start
```

### TCP 到 UDP 转发

将 TCP 连接的数据转发到 UDP 服务。

```bash
php server/tcp_to_udp_server.php start
```

### UDP 到 TCP 转发

将 UDP 数据包转发到 TCP 服务，并将 TCP 响应返回到原始 UDP 客户端。

```bash
php server/udp_to_tcp_server.php start
```

### UDP 到 UDP 转发

将 UDP 数据包转发到另一个 UDP 服务，支持 Fullcone NAT 模式，适用于 UDP 穿透场景。

```bash
php server/udp_to_udp_server.php start
```

## 客户端示例

### TCP 客户端

用于测试各种 TCP 转发服务器的简单 TCP 客户端。

```bash
php client/tcp_client.php start
```

### UDP 客户端

用于测试各种 UDP 转发服务器的简单 UDP 客户端。

```bash
php client/udp_client.php start
```

## 使用场景

1. **TCP 代理**：使用 TCP 到 TCP 转发服务器，可以实现透明的 TCP 代理功能。

2. **UDP 穿透**：使用 UDP 到 UDP 转发服务器的 Fullcone NAT 功能，可以实现 UDP 穿透，适用于游戏、音视频传输等场景。

3. **协议转换**：使用 TCP 到 UDP 或 UDP 到 TCP 转发服务器，可以实现不同协议之间的转换，解决异构系统通信问题。

4. **负载均衡**：通过修改示例，可以将请求分发到多个后端服务，实现简单的负载均衡。

5. **数据监控**：通过注册事件监听器，可以监控所有经过的数据，实现网络监控和数据分析。

## 注意事项

1. 这些示例默认使用 localhost 和预设的端口，实际部署时请根据需要修改。

2. 在生产环境中使用时，请确保添加适当的错误处理和安全措施。

3. UDP 转发服务需要考虑 NAT 穿透、客户端标识等问题，示例中已实现基本的客户端追踪逻辑。

4. 要正确测试 UDP 到 UDP 的 Fullcone NAT 功能，需要在真实的网络环境下，而不是本地回环网络。
