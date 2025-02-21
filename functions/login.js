const { createClient } = require('@supabase/supabase-js');

// Replace with your Supabase credentials from Settings > API
const supabaseUrl = 'https://otsbgixyxivywftdhhfc.supabase.co';
const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im90c2JnaXh5eGl2eXdmdGRoaGZjIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDAxMjYzOTAsImV4cCI6MjA1NTcwMjM5MH0.IxMRI_-1JhIf8jCRjYAN_WPEG6HfSHpCj09MGVXXqNc';
const supabase = createClient(supabaseUrl, supabaseKey);

exports.handler = async (event) => {
    try {
        const { agentId } = JSON.parse(event.body);
        console.log("Received agentId:", agentId);

        const { data, error } = await supabase
            .from('agents')
            .select('*')
            .eq('id', agentId)
            .single();

        if (error || !data) {
            console.log("Error or no agent found:", error);
            return {
                statusCode: 401,
                body: JSON.stringify({ error: 'Agent not approved yet' })
            };
        }

        console.log("Agent found:", data);
        return {
            statusCode: 200,
            body: JSON.stringify(data)
        };
    } catch (error) {
        console.error("Function error:", error);
        return {
            statusCode: 500,
            body: JSON.stringify({ error: 'Server error' })
        };
    }
};