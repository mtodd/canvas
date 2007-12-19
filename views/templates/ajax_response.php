<?xml version="1.0" encoding="utf-8" ?>
<ajax-response>
	<response type="object" id="<%$response_id%>">
		<%foreach from=$units item=unit key=unit_name%>
			<<%$unit_name%>>
			<%foreach from=$unit item=items key=item_name%>
				<<%$item_name%>>
				<%foreach from=$items item=item%>
					<%foreach from=$item->as_array() item=value key=property%>
						<<%$property%>><%$value%></<%$property%>>
					<%/foreach%>
				<%/foreach%>
				</<%$item_name%>>
			<%/foreach%>
			</<%$unit_name%>>
		<%/foreach%>
	</response>
</ajax-response>