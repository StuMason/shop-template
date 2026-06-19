import '@rainbow-me/rainbowkit/styles.css';

import {
    ConnectButton,
    getDefaultConfig,
    lightTheme,
    RainbowKitProvider,
} from '@rainbow-me/rainbowkit';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useMemo, useState } from 'react';
import { http } from 'viem';
import {
    WagmiProvider,
    useAccount,
    useSwitchChain,
    useWalletClient,
} from 'wagmi';
import { base, baseSepolia } from 'wagmi/chains';
import { wrapFetchWithPayment } from 'x402-fetch';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

/**
 * Config the server hands the pay page when crypto checkout is available.
 * payUrl is the signed x402 resource; the browser runs the 402 -> sign ->
 * retry dance against it with the connected wallet, and the server settles
 * via the facilitator (same trust model as every other gateway).
 */
export type CryptoCheckout = {
    payUrl: string;
    confirmUrl: string;
    projectId: string;
    network: 'base' | 'base-sepolia';
    maxAtomic: string;
    usdLabel: string;
    appName: string;
};

const queryClient = new QueryClient();

export default function UsdcCheckout({ crypto }: { crypto: CryptoCheckout }) {
    const chain = crypto.network === 'base' ? base : baseSepolia;

    const config = useMemo(
        () =>
            getDefaultConfig({
                appName: crypto.appName,
                projectId: crypto.projectId,
                chains: [chain],
                transports: { [chain.id]: http() },
                ssr: false,
            }),
        [crypto.appName, crypto.projectId, chain],
    );

    return (
        <WagmiProvider config={config}>
            <QueryClientProvider client={queryClient}>
                <RainbowKitProvider
                    theme={lightTheme({
                        accentColor: '#f8503f',
                        borderRadius: 'medium',
                    })}
                    initialChain={chain}
                >
                    <PayWithUsdc crypto={crypto} chainId={chain.id} />
                </RainbowKitProvider>
            </QueryClientProvider>
        </WagmiProvider>
    );
}

function PayWithUsdc({
    crypto,
    chainId,
}: {
    crypto: CryptoCheckout;
    chainId: number;
}) {
    const { isConnected, chainId: currentChainId } = useAccount();
    const { data: walletClient } = useWalletClient();
    const { switchChain, isPending: switching } = useSwitchChain();
    const [paying, setPaying] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const onWrongChain = isConnected && currentChainId !== chainId;

    async function pay() {
        if (!walletClient) {
            return;
        }

        setPaying(true);
        setError(null);

        try {
            const fetchWithPayment = wrapFetchWithPayment(
                window.fetch,
                walletClient as Parameters<typeof wrapFetchWithPayment>[1],
                BigInt(crypto.maxAtomic),
            );

            const response = await fetchWithPayment(crypto.payUrl);

            if (!response.ok) {
                throw new Error('settle-failed');
            }

            window.location.assign(crypto.confirmUrl);
        } catch (e) {
            setPaying(false);
            setError(humaniseError(e instanceof Error ? e.message : ''));
        }
    }

    return (
        <div className="space-y-4">
            <div className="flex justify-center">
                <ConnectButton
                    chainStatus="icon"
                    showBalance={false}
                    accountStatus="address"
                />
            </div>

            {isConnected && onWrongChain && (
                <Button
                    variant="outline"
                    size="lg"
                    className="w-full"
                    disabled={switching}
                    onClick={() => switchChain({ chainId })}
                >
                    Switch your wallet to Base
                </Button>
            )}

            {isConnected && !onWrongChain && (
                <Button
                    size="lg"
                    className="w-full"
                    disabled={paying || !walletClient}
                    onClick={pay}
                >
                    {paying ? (
                        <>
                            <Spinner className="size-4" aria-hidden="true" />
                            Confirm in your wallet…
                        </>
                    ) : (
                        `Pay ${crypto.usdLabel} in USDC`
                    )}
                </Button>
            )}

            {error && (
                <p
                    className="text-center text-sm text-destructive"
                    role="alert"
                >
                    {error}
                </p>
            )}

            <p className="text-center text-xs text-muted-foreground">
                Pay in USDC on Base. Gas is sponsored, so you only need USDC —
                not ETH.
            </p>
        </div>
    );
}

function humaniseError(message: string): string {
    if (/user rejected|denied|rejected the request|cancel/i.test(message)) {
        return 'You cancelled the signature — no payment was made.';
    }

    if (/insufficient/i.test(message)) {
        return 'Your wallet doesn’t have enough USDC on Base for this order.';
    }

    return 'The payment could not be settled. Please try again.';
}
