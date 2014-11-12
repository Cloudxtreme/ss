--[[ $%BEGINLICENSE%$
 Copyright (c) 2007, 2012, Oracle and/or its affiliates. All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License as
 published by the Free Software Foundation; version 2 of the
 License.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA
 02110-1301  USA

 $%ENDLICENSE%$ --]]

---
-- a flexible statement based load balancer with connection pooling
--
-- * build a connection pool of min_idle_connections for each backend and maintain
--   its size
-- * 
-- 
-- 

local commands    = require("proxy.commands")
local tokenizer   = require("proxy.tokenizer")
local lb          = require("proxy.balance")
local auto_config = require("proxy.auto-config")
local parser	  = require("proxy.parser")

--- config
--
-- connection pool
if not proxy.global.config.rwsplit then
	proxy.global.config.rwsplit = {
		is_debug = false
	}
end

local is_in_transaction       = false

_G.do_date = ""
_G.sql_num = 0

-- if this was a SELECT SQL_CALC_FOUND_ROWS ... stay on the same connections
local is_in_select_calc_found_rows = false

--- 
-- get a connection to a backend
--
-- as long as we don't have enough connections in the pool, create new connections
--
function connect_server() 
	proxy.connection.backend_ndx = 1
end

--- 
-- put the successfully authed connection into the connection pool
--
-- @param auth the context information for the auth
--
-- auth.packet is the packet

--- 
function read_query( packet )

	if proxy.connection.backend_ndx == 0 then
		proxy.connection.backend_ndx = 1
	end


	if packet:byte() == proxy.COM_QUERY then
		local query_type = string.lower(packet:sub(2, 7))
		if query_type == "insert" or
			query_type == "update" or
			query_type == "delete" then
	
			--local cmd = commands.parse(packet)
			--print("cmd_query:" .. cmd.query)

			local date = os.date("%Y-%m-%d")
			--local time = os.date("%H:%M")
			local folder = "/opt/db_mgr/log/proxy/" .. proxy.connection.client.dst.port
			local logfile = folder .. "/" .. date .. ".log"
			--print("port:" .. proxy.connection.client.dst.port)
			--print("folder:" .. folder)
			--print("filename:" .. logfile)

			if date ~= _G.do_date then
				_G.do_date = date
				_G.sql_num = 0
				local comd = "mkdir -p " .. folder
				local hand = io.open(logfile, "r")
				os.execute(comd)
				if hand then
					local curr_sql = ""
					local last_sql = ""
        				for line in hand:lines() do
						tag = string.sub(line, 1, string.len("efly-db-proxy-done"))
						if tag ~= "efly-db-proxy-done" then
							curr_sql = curr_sql .. line
							last_sql = curr_sql
						else
							curr_sql = ""
						end
        				end
					hand:close()
					if string.len(last_sql) > 0 then
						pos = string.find(last_sql, " ")
						_G.sql_num = string.sub(last_sql, 1, pos-1)
					end
				end
			end

			_G.sql_num = _G.sql_num + 1
			local out = io.open(logfile, "a+")
			out:write(_G.sql_num .. " " .. proxy.connection.server.dst.name .. " " .. proxy.connection.client.default_db .. " " .. packet:sub(2) .. "\nefly-db-proxy-done " .. _G.sql_num .. "\n")
			--out:write("efly-db-proxy-done " .. _G.sql_num .. "\n")
			out:close()
		end
	end
	--if packet:sub(2, 7) == "insert" then
	--	print("insert sql do!!")
		--proxy.connection.backend_ndx = 1
		--proxy.queries:append(1, packet, {backend_ndx = 1})
	--	proxy.queries:append(1, packet, {resultset_is_needed=true})
	--	proxy.queries:append(2, string.char(proxy.COM_QUERY) .. "insert into test values(1122)", {resultset_is_needed=true})
	--	return proxy.PROXY_SEND_QUERY
	--end

	return proxy.PROXY_SEND_QUERY
end

---
-- as long as we are in a transaction keep the connection
-- otherwise release it so another client can use it
function disconnect_client()
	local is_debug = proxy.global.config.rwsplit.is_debug
	if is_debug then
		print("[disconnect_client] " .. proxy.connection.client.src.name)
	end

	-- make sure we are disconnection from the connection
	-- to move the connection into the pool
	proxy.connection.backend_ndx = 0
end


