Use this information to answer the subsequent question. Ignore any `null` fields. Do not add any markdown formatting. Respond with a single sentence and nothing else.

{{ collect($data)->map(fn ($value, $key) => "$key: $value")->implode(', ') }}
