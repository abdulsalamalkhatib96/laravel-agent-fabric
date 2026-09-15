# Protocols

## MCP

`McpClient` abstracts remote MCP transport. `McpAgentTool` lets a remote MCP tool participate in Agent Fabric governance. `LocalMcpServer` exposes a local `ToolRegistry` through MCP-style discovery/invocation while retaining ToolExecutor controls.

OAuth, HTTP transport, streamable HTTP, and vendor-specific MCP authentication should be implemented by adapters/plugins.

## A2A

`A2AClient` supports remote agent discovery/delegation. `A2AAgentTool` turns delegation into a governed tool. `LocalA2AGateway` exposes local agent cards and performs tenant/actor-aware delegation.

MCP and A2A are not interchangeable: MCP is primarily tool/resource interoperability; A2A represents agent-to-agent task delegation.
